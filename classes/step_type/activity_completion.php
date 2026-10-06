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
 * classes/local/step_type/activity_completion.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\step_type;


use core\event\course_module_completion_updated;
use local_xpquests\event_step_type_interface;
use stdClass;
use Throwable;

/**
 * Activity completion.
 */
class activity_completion extends base implements event_step_type_interface {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('steptype_activity_completion', 'local_xpquests');
    }

    /**
     * Validate configuration.
     */
    public function validate_configuration(array $config, int $courseid): array {
        return ['cmid' => $this->require_cmid($config, $courseid)];
    }

    /**
     * Is completed.
     */
    public function is_completed(int $userid, stdClass $step, stdClass $progress): bool {
        global $DB;
        $config = $this->config($step);
        $cmid = (int)($config['cmid'] ?? 0);
        if (!$cmid || !$DB->record_exists('course_modules', ['id' => $cmid])) {
            return false;
        }
        $record = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cmid, 'userid' => $userid]);
        return $record
            && (int)$record->completionstate !== COMPLETION_INCOMPLETE
            && (int)$record->timemodified >= $this->availability_time($step, $progress);
    }

    /**
     * Supports event.
     */
    public function supports_event(\core\event\base $event): bool {
        return $event instanceof course_module_completion_updated;
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
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0)
            && (int)$event->relateduserid === $userid
            && $this->is_completed($userid, $step, $progress);
    }

    /**
     * Get description.
     */
    public function get_description(stdClass $step): string {
        $config = $this->config($step);
        try {
            $cm = $this->get_cminfo((int)($config['cmid'] ?? 0));
            return get_string('stepdesc_activity_completion', 'local_xpquests', $cm->name);
        } catch (Throwable $e) {
            return get_string('step_activity_missing', 'local_xpquests');
        }
    }
}
