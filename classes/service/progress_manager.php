<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/service/progress_manager.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\service;


use core\lock\lock_config;
use dml_write_exception;
use local_xpquests\event\quest_completed;
use local_xpquests\event\quest_started;
use local_xpquests\event\quest_step_completed;
use local_xpquests\integration\celebration_provider;
use local_xpquests\integration\personalxp_provider;
use local_xpquests\integration\xp_provider_interface;
use stdClass;
use Throwable;

/**
 * Progress manager.
 */
class progress_manager {
    /** @var reward_manager */
    private $rewards;
    /** @var xp_provider_interface */
    private $xp;

    /**
     * Create a new instance.
     */
    public function __construct(
        ?reward_manager $rewards = null,
        ?xp_provider_interface $xp = null
    ) {
        $this->xp = $xp ?: new personalxp_provider();
        $this->rewards = $rewards ?: new reward_manager($this->xp);
    }

    /**
     * Quest is open.
     */
    public static function quest_is_open(stdClass $quest, ?int $time = null): bool {
        $time = $time ?: time();
        if (empty($quest->enabled)) {
            return false;
        }
        if (!empty($quest->timestart) && (int)$quest->timestart > $time) {
            return false;
        }
        if (!empty($quest->timeend) && (int)$quest->timeend < $time) {
            return false;
        }
        return true;
    }

    /**
     * Get or create progress.
     */
    public function get_or_create_progress(stdClass $quest, int $userid): ?stdClass {
        global $DB;
        $active = $DB->get_record('local_xpquests_progress', [
            'questid' => $quest->id,
            'userid' => $userid,
            'status' => 'inprogress',
        ]);
        if ($active) {
            if (!empty($quest->timeend) && (int)$quest->timeend < time()) {
                $active->status = 'expired';
                $active->timemodified = time();
                $DB->update_record('local_xpquests_progress', $active);
                return null;
            }
            return $active;
        }
        if (!self::quest_is_open($quest)) {
            return null;
        }

        $completed = (int)$DB->count_records('local_xpquests_progress', [
            'questid' => $quest->id,
            'userid' => $userid,
            'status' => 'completed',
        ]);
        if (empty($quest->repeatable) && $completed > 0) {
            return $DB->get_record_sql(
                "SELECT * FROM {local_xpquests_progress}
                  WHERE questid = :questid AND userid = :userid AND status = 'completed'
               ORDER BY runnumber DESC",
                ['questid' => $quest->id, 'userid' => $userid], IGNORE_MULTIPLE
            );
        }
        if (!empty($quest->repeatable) && (int)$quest->maxcompletions > 0
            && $completed >= (int)$quest->maxcompletions) {
            return null;
        }

        $factory = lock_config::get_lock_factory('local_xpquests');
        $lock = $factory->get_lock('start_' . $quest->id . '_' . $userid, 10);
        if (!$lock) {
            return null;
        }
        try {
            $active = $DB->get_record('local_xpquests_progress', [
                'questid' => $quest->id,
                'userid' => $userid,
                'status' => 'inprogress',
            ]);
            if ($active) {
                return $active;
            }
            $runnumber = 1 + (int)$DB->get_field_sql(
                    'SELECT COALESCE(MAX(runnumber), 0) FROM {local_xpquests_progress} WHERE questid = :q AND userid = :u',
                    ['q' => $quest->id, 'u' => $userid]
                );
            $now = time();
            $progress = (object)[
                'questid' => $quest->id,
                'userid' => $userid,
                'status' => 'inprogress',
                'currentstep' => 0,
                'startedat' => $now,
                'completedat' => 0,
                'completioncount' => $completed,
                'runnumber' => $runnumber,
                'startxp' => $this->xp->get_total($userid, (int)$quest->courseid),
                'timemodified' => $now,
            ];
            $progress->id = $DB->insert_record('local_xpquests_progress', $progress);
            quest_started::create_for_progress($quest, $progress)->trigger();
            return $progress;
        } finally {
            $lock->release();
        }
    }

    /**
     * Is step available.
     */
    public function is_step_available(stdClass $quest, stdClass $step, stdClass $progress): bool {
        global $DB;
        if (empty($quest->sequential)) {
            return true;
        }
        $sql = "SELECT s.id
                  FROM {local_xpquests_steps} s
             LEFT JOIN {local_xpquests_step_progress} sp
                    ON sp.stepid = s.id AND sp.progressid = :progressid
                 WHERE s.questid = :questid
                   AND s.sortorder < :sortorder
                   AND s.optional = 0
                   AND sp.id IS NULL";
        return !$DB->record_exists_sql($sql, [
            'progressid' => $progress->id,
            'questid' => $quest->id,
            'sortorder' => $step->sortorder,
        ]);
    }

    /**
     * Mark step completed.
     */
    public function mark_step_completed(
        stdClass $quest,
        stdClass $step,
        stdClass $progress,
        ?int $completedat = null
    ): bool {
        global $DB;
        if ($progress->status !== 'inprogress') {
            return false;
        }
        if (!$this->is_step_available($quest, $step, $progress)) {
            return false;
        }
        if ($DB->record_exists('local_xpquests_step_progress', [
            'progressid' => $progress->id,
            'stepid' => $step->id,
        ])) {
            return false;
        }

        $now = $completedat ?: time();
        try {
            $id = $DB->insert_record('local_xpquests_step_progress', (object)[
                'questid' => $quest->id,
                'progressid' => $progress->id,
                'stepid' => $step->id,
                'userid' => $progress->userid,
                'status' => 'completed',
                'completedat' => $now,
                'timecreated' => $now,
            ]);
        } catch (dml_write_exception $e) {
            return false;
        }

        $progress->currentstep = (int)$step->sortorder;
        $progress->timemodified = $now;
        $DB->update_record('local_xpquests_progress', $progress);
        quest_step_completed::create_for_step($quest, $step, $progress, $id)->trigger();
        $this->complete_if_ready($quest, $progress);
        return true;
    }

    /**
     * Recalculate.
     */
    public function recalculate(stdClass $quest, stdClass $progress): stdClass {
        global $DB;
        if ($progress->status !== 'inprogress') {
            if ($progress->status === 'completed') {
                try {
                    $this->rewards->retry($quest, $progress);
                } catch (Throwable $e) {
                    debugging('XP Quest reward retry failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
            return $progress;
        }
        if (!empty($quest->timeend) && (int)$quest->timeend < time()) {
            $progress->status = 'expired';
            $progress->timemodified = time();
            $DB->update_record('local_xpquests_progress', $progress);
            return $progress;
        }

        $steps = $DB->get_records('local_xpquests_steps', ['questid' => $quest->id], 'sortorder ASC, id ASC');
        foreach ($steps as $step) {
            if ($DB->record_exists('local_xpquests_step_progress', [
                'progressid' => $progress->id,
                'stepid' => $step->id,
            ])) {
                continue;
            }
            if (!$this->is_step_available($quest, $step, $progress)) {
                continue;
            }
            if ($step->steptype === 'activity_view' || $step->steptype === 'manual') {
                continue;
            }
            try {
                $type = step_type_registry::get($step->steptype);
                if ($type->is_completed((int)$progress->userid, $step, $progress)) {
                    $this->mark_step_completed($quest, $step, $progress);
                    $progress = $DB->get_record('local_xpquests_progress', ['id' => $progress->id], '*', MUST_EXIST);
                    if ($progress->status !== 'inprogress') {
                        break;
                    }
                }
            } catch (Throwable $e) {
                debugging('XP Quest step recalculation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return $DB->get_record('local_xpquests_progress', ['id' => $progress->id], '*', MUST_EXIST);
    }

    /**
     * Complete if ready.
     */
    public function complete_if_ready(stdClass $quest, stdClass $progress): bool {
        global $DB;
        $required = (int)$DB->count_records('local_xpquests_steps', [
            'questid' => $quest->id,
            'optional' => 0,
        ]);
        if ($required === 0) {
            return false;
        }
        $sql = "SELECT COUNT(sp.id)
                  FROM {local_xpquests_step_progress} sp
                  JOIN {local_xpquests_steps} s ON s.id = sp.stepid
                 WHERE sp.progressid = :progressid
                   AND s.optional = 0
                   AND sp.status = 'completed'";
        $done = (int)$DB->count_records_sql($sql, ['progressid' => $progress->id]);
        if ($done < $required) {
            return false;
        }

        $factory = lock_config::get_lock_factory('local_xpquests');
        $lock = $factory->get_lock('complete_' . $progress->id, 10);
        if (!$lock) {
            return false;
        }
        try {
            $progress = $DB->get_record('local_xpquests_progress', ['id' => $progress->id], '*', MUST_EXIST);
            if ($progress->status === 'completed') {
                return false;
            }
            $previous = (int)$DB->count_records_select('local_xpquests_progress',
                'questid = :q AND userid = :u AND status = :status AND id <> :id', [
                    'q' => $quest->id,
                    'u' => $progress->userid,
                    'status' => 'completed',
                    'id' => $progress->id,
                ]);
            $progress->status = 'completed';
            $progress->completedat = time();
            $progress->completioncount = $previous + 1;
            $progress->timemodified = time();
            $DB->update_record('local_xpquests_progress', $progress);

            quest_completed::create_for_progress($quest, $progress)->trigger();
            try {
                $this->rewards->deliver($quest, $progress);
            } catch (Throwable $rewarderror) {
                // Completion is durable. The scheduled task retries the same idempotency reference.
                debugging('XP Quest reward delivery queued for retry: ' . $rewarderror->getMessage(), DEBUG_DEVELOPER);
            }
            celebration_provider::queue_completed($quest, $progress);
            return true;
        } catch (Throwable $e) {
            debugging('XP Quest completion failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        } finally {
            $lock->release();
        }
    }

    /**
     * Summary.
     */
    public function summary(stdClass $quest, ?stdClass $progress): array {
        global $DB;
        $steps = $DB->get_records('local_xpquests_steps', ['questid' => $quest->id], 'sortorder ASC, id ASC');
        $items = [];
        $required = 0;
        $requireddone = 0;
        foreach ($steps as $step) {
            if (empty($step->optional)) {
                $required++;
            }
            $completed = false;
            $available = !$quest->sequential;
            if ($progress) {
                $completed = $DB->record_exists('local_xpquests_step_progress', [
                    'progressid' => $progress->id,
                    'stepid' => $step->id,
                    'status' => 'completed',
                ]);
                $available = $progress->status === 'inprogress' && $this->is_step_available($quest, $step, $progress);
            }
            if ($completed && empty($step->optional)) {
                $requireddone++;
            }
            try {
                $type = step_type_registry::get($step->steptype);
                $description = $type->get_description($step);
            } catch (Throwable $e) {
                $description = get_string('step_unavailable', 'local_xpquests');
            }
            // Page rendering reads our persisted progress only. Expensive state/history
            // reconciliation happens in observers, explicit API calls, and cron.
            $detail = null;
            $items[] = [
                'id' => (int)$step->id,
                'name' => format_string($step->name),
                'description' => $description,
                'type' => $step->steptype,
                'optional' => !empty($step->optional),
                'completed' => $completed,
                'available' => $available || $completed,
                'locked' => !$completed && !$available,
                'progress' => $detail,
            ];
        }
        $percent = $required > 0 ? (int)round(($requireddone / $required) * 100) : 0;
        return [
            'required' => $required,
            'completed' => $requireddone,
            'percent' => $percent,
            'steps' => $items,
            'status' => $progress ? $progress->status : 'notstarted',
            'runnumber' => $progress ? (int)$progress->runnumber : 0,
        ];
    }
}
