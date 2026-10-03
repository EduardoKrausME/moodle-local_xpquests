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
 * classes/local/step_type/assignment_submission.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\step_type;


use local_xpquests\event_step_type_interface;
use mod_assign\event\assessable_submitted;
use stdClass;
use Throwable;

/**
 * Assignment submission.
 */
class assignment_submission extends base implements event_step_type_interface {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('steptype_assignment_submission', 'local_xpquests');
    }

    /**
     * Validate configuration.
     */
    public function validate_configuration(array $config, int $courseid): array {
        $cmid = $this->require_cmid($config, $courseid);
        get_coursemodule_from_id('assign', $cmid, $courseid, false, MUST_EXIST);
        return ['cmid' => $cmid];
    }

    /**
     * Is completed.
     */
    public function is_completed(int $userid, stdClass $step, stdClass $progress): bool {
        global $DB;
        $config = $this->config($step);
        $cm = get_coursemodule_from_id('assign', (int)($config['cmid'] ?? 0), 0, false, IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        return $DB->record_exists_select('assign_submission',
            'assignment = :assignment AND userid = :userid AND latest = 1 AND status = :status AND timemodified >= :mintime', [
                'assignment' => $cm->instance,
                'userid' => $userid,
                'status' => 'submitted',
                'mintime' => $this->availability_time($step, $progress),
            ]);
    }

    /**
     * Supports event.
     */
    public function supports_event(\core\event\base $event): bool {
        return $event instanceof assessable_submitted;
    }

    /**
     * Matches event.
     */
    public function matches_event(\core\event\base $event, stdClass $step): bool {
        $config = $this->config($step);
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0);
    }

    /**
     * Is completed by event.
     */
    public function is_completed_by_event(
        \core\event\base $event,
        int $userid,
        stdClass $step,
        stdClass $progress
    ): bool {
        $config = $this->config($step);
        $eventuserid = !empty($event->relateduserid) ? (int)$event->relateduserid : (int)$event->userid;
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0)
            && $eventuserid === $userid
            && $this->is_completed($userid, $step, $progress);
    }

    /**
     * Get description.
     */
    public function get_description(stdClass $step): string {
        $config = $this->config($step);
        try {
            $cm = $this->get_cminfo((int)($config['cmid'] ?? 0));
            return get_string('stepdesc_assignment_submission', 'local_xpquests', $cm->name);
        } catch (Throwable $e) {
            return get_string('step_activity_missing', 'local_xpquests');
        }
    }
}
