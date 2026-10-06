<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * classes/api.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;


use context_course;
use invalid_parameter_exception;
use local_xpquests\service\progress_manager;
use moodle_exception;
use stdClass;

/**
 * Api.
 */
class api {
    /**
     * Get available quests.
     */
    public static function get_available_quests(int $courseid, int $userid): array {
        global $DB;
        $context = context_course::instance($courseid);
        if (!has_capability('local/xpquests:view', $context, $userid)) {
            return [];
        }
        $now = time();
        $quests = $DB->get_records_select('local_xpquests_quests',
            'courseid = :courseid AND enabled = 1 AND (timestart = 0 OR timestart <= :now1) AND (timeend = 0 OR timeend >= :now2)',
            ['courseid' => $courseid, 'now1' => $now, 'now2' => $now], 'sortorder ASC, id ASC');
        $manager = new progress_manager();
        $result = [];
        foreach ($quests as $quest) {
            $completed = (int)$DB->count_records('local_xpquests_progress', [
                'questid' => $quest->id,
                'userid' => $userid,
                'status' => 'completed',
            ]);
            if (empty($quest->repeatable) && $completed > 0) {
                $progress = $DB->get_record_sql(
                    "SELECT * FROM {local_xpquests_progress} WHERE questid = :q AND userid = :u ORDER BY runnumber DESC",
                    ['q' => $quest->id, 'u' => $userid], IGNORE_MULTIPLE
                );
            } else if (!empty($quest->repeatable) && (int)$quest->maxcompletions > 0
                && $completed >= (int)$quest->maxcompletions) {
                $progress = $DB->get_record_sql(
                    "SELECT * FROM {local_xpquests_progress} WHERE questid = :q AND userid = :u ORDER BY runnumber DESC",
                    ['q' => $quest->id, 'u' => $userid], IGNORE_MULTIPLE
                );
            } else {
                $progress = $DB->get_record('local_xpquests_progress', [
                    'questid' => $quest->id,
                    'userid' => $userid,
                    'status' => 'inprogress',
                ]);
            }
            $summary = $manager->summary($quest, $progress ?: null);
            $result[] = self::quest_to_array($quest, $summary, $completed);
        }
        return $result;
    }

    /**
     * Get user progress.
     */
    public static function get_user_progress(int $courseid, int $userid): array {
        global $DB;
        $quests = $DB->get_records('local_xpquests_quests', ['courseid' => $courseid], 'sortorder ASC, id ASC');
        $manager = new progress_manager();
        $result = [];
        foreach ($quests as $quest) {
            $progress = $DB->get_record_sql(
                'SELECT * FROM {local_xpquests_progress} WHERE questid = :q AND userid = :u ORDER BY runnumber DESC',
                ['q' => $quest->id, 'u' => $userid], IGNORE_MULTIPLE
            );
            if ($progress) {
                $result[] = self::quest_to_array($quest, $manager->summary($quest, $progress), (int)$progress->completioncount);
            }
        }
        return $result;
    }

    /**
     * Get quest progress.
     */
    public static function get_quest_progress(int $questid, int $userid, bool $start = false): array {
        global $DB;
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
        $context = context_course::instance($quest->courseid);
        require_capability('local/xpquests:view', $context, $userid);
        $manager = new progress_manager();
        $progress = $DB->get_record('local_xpquests_progress', [
            'questid' => $questid,
            'userid' => $userid,
            'status' => 'inprogress',
        ]);
        if (!$progress && $start) {
            $progress = $manager->get_or_create_progress($quest, $userid);
        }
        if (!$progress) {
            $progress = $DB->get_record_sql(
                'SELECT * FROM {local_xpquests_progress} WHERE questid = :q AND userid = :u ORDER BY runnumber DESC',
                ['q' => $questid, 'u' => $userid], IGNORE_MULTIPLE
            );
        }
        return self::quest_to_array($quest, $manager->summary($quest, $progress ?: null),
            $progress ? (int)$progress->completioncount : 0);
    }

    /**
     * Mark manual step.
     */
    public static function mark_manual_step(int $stepid, int $userid): array {
        global $DB, $USER;
        $step = $DB->get_record('local_xpquests_steps', ['id' => $stepid], '*', MUST_EXIST);
        if ($step->steptype !== 'manual') {
            throw new invalid_parameter_exception('Only manual steps can be marked manually.');
        }
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], '*', MUST_EXIST);
        $context = context_course::instance($quest->courseid);
        require_capability('local/xpquests:markmanual', $context);
        if (!has_capability('local/xpquests:view', $context, $userid)) {
            throw new invalid_parameter_exception('The target user cannot participate in this quest.');
        }

        $manager = new progress_manager();
        $progress = $manager->get_or_create_progress($quest, $userid);
        if (!$progress || $progress->status !== 'inprogress') {
            throw new moodle_exception('questnotactive', 'local_xpquests');
        }
        $manager->mark_step_completed($quest, $step, $progress);
        return self::get_quest_progress($quest->id, $userid, false);
    }

    /**
     * Recalculate progress.
     */
    public static function recalculate_progress(int $questid, int $userid): array {
        global $DB;
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
        $progress = $DB->get_record('local_xpquests_progress', [
            'questid' => $questid,
            'userid' => $userid,
            'status' => 'inprogress',
        ]);
        if (!$progress) {
            return self::get_quest_progress($questid, $userid, false);
        }
        $manager = new progress_manager();
        $manager->recalculate($quest, $progress);
        return self::get_quest_progress($questid, $userid, false);
    }

    /**
     * Quest to array.
     */
    private static function quest_to_array(stdClass $quest, array $summary, int $completioncount): array {
        return [
            'id' => (int)$quest->id,
            'courseid' => (int)$quest->courseid,
            'name' => format_string($quest->name),
            'description' => format_text($quest->description ?: '', FORMAT_HTML, ['filter' => true]),
            'image' => $quest->image ?: '',
            'sequential' => !empty($quest->sequential),
            'repeatable' => !empty($quest->repeatable),
            'maxcompletions' => (int)$quest->maxcompletions,
            'completioncount' => $completioncount,
            'rewardxp' => (int)$quest->rewardxp,
            'rewardcredits' => (int)$quest->rewardcredits,
            'timestart' => (int)$quest->timestart,
            'timeend' => (int)$quest->timeend,
            'status' => $summary['status'],
            'completedsteps' => $summary['completed'],
            'requiredsteps' => $summary['required'],
            'percent' => $summary['percent'],
            'runnumber' => $summary['runnumber'],
            'steps' => $summary['steps'],
        ];
    }
}
