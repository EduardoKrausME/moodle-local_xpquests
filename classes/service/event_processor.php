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
 * classes/service/event_processor.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\service;


use core\event\base;
use core\event\course_module_completion_updated;
use local_xpquests\event_step_type_interface;
use mod_assign\event\assessable_submitted;
use Throwable;

/**
 * Event processor.
 */
class event_processor {
    /**
     * Observe.
     */
    public static function observe(base $event): void {
        global $DB;
        $courseid = (int)$event->courseid;
        if (!$courseid) {
            return;
        }
        $userid = self::get_target_userid($event);
        if (!$userid) {
            return;
        }

        $now = (int)$event->timecreated ?: time();
        $params = ['courseid' => $courseid, 'now1' => $now, 'now2' => $now];
        $quests = $DB->get_records_select('local_xpquests_quests',
            'courseid = :courseid AND enabled = 1 AND (timestart = 0 OR timestart <= :now1) AND (timeend = 0 OR timeend >= :now2)',
            $params, 'sortorder ASC, id ASC');
        if (!$quests) {
            return;
        }

        $manager = new progress_manager();
        foreach ($quests as $quest) {
            $steps = $DB->get_records('local_xpquests_steps', ['questid' => $quest->id], 'sortorder ASC, id ASC');
            if (!$steps) {
                continue;
            }
            $progress = $DB->get_record('local_xpquests_progress', [
                'questid' => $quest->id,
                'userid' => $userid,
                'status' => 'inprogress',
            ]);

            foreach ($steps as $step) {
                try {
                    $type = step_type_registry::get($step->steptype);
                    if (!$type instanceof event_step_type_interface || !$type->supports_event($event)) {
                        continue;
                    }
                    if (!$progress) {
                        $candidate = (object)[
                            'id' => 0,
                            'questid' => $quest->id,
                            'userid' => $userid,
                            'status' => 'inprogress',
                            'startedat' => $now,
                            'startxp' => 0,
                        ];
                        if (!$manager->is_step_available($quest, $step, $candidate)
                            || !$type->matches_event($event, $step)) {
                            continue;
                        }
                        $progress = $manager->get_or_create_progress($quest, $userid);
                        if (!$progress) {
                            break;
                        }
                    }
                    if (!$manager->is_step_available($quest, $step, $progress)) {
                        continue;
                    }
                    if ($type->is_completed_by_event($event, $userid, $step, $progress)) {
                        $completed = $manager->mark_step_completed($quest, $step, $progress, $now);
                        $progress = $DB->get_record('local_xpquests_progress', ['id' => $progress->id], '*', MUST_EXIST);
                        if ($progress->status !== 'inprogress') {
                            break;
                        }
                        // In sequential quests a single Moodle event may complete at most one step.
                        // Otherwise two consecutive steps configured for the same event could both
                        // be consumed by one action even though the second was locked when it happened.
                        if ($completed && !empty($quest->sequential)) {
                            break;
                        }
                    }
                } catch (Throwable $e) {
                    debugging('XP Quest observer ignored an event: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Get target userid.
     */
    private static function get_target_userid(base $event): int {
        if (($event instanceof course_module_completion_updated
                || $event instanceof assessable_submitted)
            && !empty($event->relateduserid)) {
            return (int)$event->relateduserid;
        }
        return (int)$event->userid;
    }
}
