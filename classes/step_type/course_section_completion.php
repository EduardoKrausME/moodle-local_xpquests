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
 * classes/local/step_type/course_section_completion.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\step_type;


use core\event\course_module_completion_updated;
use invalid_parameter_exception;
use local_xpquests\event_step_type_interface;
use stdClass;

/**
 * Course section completion.
 */
class course_section_completion extends base implements event_step_type_interface {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('steptype_course_section_completion', 'local_xpquests');
    }

    /**
     * Validate configuration.
     */
    public function validate_configuration(array $config, int $courseid): array {
        global $DB;
        $sectionnum = max(0, (int)($config['sectionnum'] ?? -1));
        if (!$DB->record_exists('course_sections', ['course' => $courseid, 'section' => $sectionnum])) {
            throw new invalid_parameter_exception('Invalid course section.');
        }
        return ['sectionnum' => $sectionnum];
    }

    /**
     * Is completed.
     */
    public function is_completed(int $userid, stdClass $step, stdClass $progress): bool {
        global $DB;
        $config = $this->config($step);
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], 'courseid', MUST_EXIST);
        $section = $DB->get_record('course_sections', [
            'course' => $quest->courseid,
            'section' => (int)($config['sectionnum'] ?? -1),
        ]);
        if (!$section) {
            return false;
        }
        $cms = $DB->get_records_select('course_modules',
            'course = :course AND section = :section AND completion <> :none AND deletioninprogress = 0', [
                'course' => $quest->courseid,
                'section' => $section->id,
                'none' => COMPLETION_TRACKING_NONE,
            ]);
        if (!$cms) {
            return false;
        }
        $lastcompletion = 0;
        foreach ($cms as $cm) {
            $completion = $DB->get_record('course_modules_completion', [
                'coursemoduleid' => $cm->id,
                'userid' => $userid,
            ], 'completionstate, timemodified');
            if (!$completion || (int)$completion->completionstate === COMPLETION_INCOMPLETE) {
                return false;
            }
            $lastcompletion = max($lastcompletion, (int)$completion->timemodified);
        }
        // A section that was already fully complete before this step became available
        // must not silently consume a sequential quest step.
        return $lastcompletion >= $this->availability_time($step, $progress);
    }

    /**
     * Get progress.
     */
    public function get_progress(int $userid, stdClass $step, stdClass $progress): array {
        global $DB;
        $config = $this->config($step);
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], 'courseid', MUST_EXIST);
        $section = $DB->get_record('course_sections', [
            'course' => $quest->courseid,
            'section' => (int)($config['sectionnum'] ?? -1),
        ]);
        if (!$section) {
            return ['current' => 0.0, 'target' => 1.0, 'completed' => false];
        }
        $cms = $DB->get_records_select('course_modules',
            'course = :course AND section = :section AND completion <> :none AND deletioninprogress = 0', [
                'course' => $quest->courseid,
                'section' => $section->id,
                'none' => COMPLETION_TRACKING_NONE,
            ]);
        $target = count($cms);
        $current = 0;
        foreach ($cms as $cm) {
            $state = $DB->get_field('course_modules_completion', 'completionstate', [
                'coursemoduleid' => $cm->id,
                'userid' => $userid,
            ]);
            if ($state !== false && (int)$state !== COMPLETION_INCOMPLETE) {
                $current++;
            }
        }
        return [
            'current' => (float)$current,
            'target' => (float)max(1, $target),
            'completed' => $target > 0 && $current >= $target && $this->is_completed($userid, $step, $progress),
        ];
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
        global $DB;
        if (!$this->supports_event($event)) {
            return false;
        }
        $config = $this->config($step);
        $cm = $DB->get_record('course_modules', ['id' => (int)$event->contextinstanceid], 'id, section');
        if (!$cm) {
            return false;
        }
        $section = $DB->get_record('course_sections', ['id' => $cm->section], 'section');
        return $section && (int)$section->section === (int)($config['sectionnum'] ?? -1);
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
        return $this->supports_event($event)
            && (int)$event->relateduserid === $userid
            && $this->is_completed($userid, $step, $progress);
    }

    /**
     * Get description.
     */
    public function get_description(stdClass $step): string {
        $config = $this->config($step);
        return get_string('stepdesc_course_section_completion', 'local_xpquests', (int)($config['sectionnum'] ?? 0));
    }
}
