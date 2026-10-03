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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/local/step_type/quiz_attempt.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\local\step_type;

defined('MOODLE_INTERNAL') || die();

class quiz_attempt extends base implements \local_xpquests\event_step_type_interface {
    public function get_name(): string {
        return get_string('steptype_quiz_attempt', 'local_xpquests');
    }

    public function validate_configuration(array $config, int $courseid): array {
        $cmid = $this->require_cmid($config, $courseid);
        $cm = get_coursemodule_from_id('quiz', $cmid, $courseid, false, MUST_EXIST);
        return ['cmid' => (int)$cm->id];
    }

    public function is_completed(int $userid, \stdClass $step, \stdClass $progress): bool {
        global $DB;
        $config = $this->config($step);
        $cm = get_coursemodule_from_id('quiz', (int)($config['cmid'] ?? 0), 0, false, IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        return $DB->record_exists_select('quiz_attempts',
            'quiz = :quiz AND userid = :userid AND preview = 0 AND timefinish >= :mintime', [
                'quiz' => $cm->instance,
                'userid' => $userid,
                'mintime' => $this->availability_time($step, $progress),
            ]);
    }

    public function supports_event(\core\event\base $event): bool {
        return $event instanceof \mod_quiz\event\attempt_submitted;
    }

    public function matches_event(\core\event\base $event, \stdClass $step): bool {
        $config = $this->config($step);
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0);
    }

    public function is_completed_by_event(
        \core\event\base $event,
        int $userid,
        \stdClass $step,
        \stdClass $progress
    ): bool {
        $config = $this->config($step);
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0)
            && (int)$event->userid === $userid
            && $event->timecreated >= $this->availability_time($step, $progress);
    }

    public function get_description(\stdClass $step): string {
        $config = $this->config($step);
        try {
            $cm = $this->get_cminfo((int)($config['cmid'] ?? 0));
            return get_string('stepdesc_quiz_attempt', 'local_xpquests', $cm->name);
        } catch (\Throwable $e) {
            return get_string('step_activity_missing', 'local_xpquests');
        }
    }
}
