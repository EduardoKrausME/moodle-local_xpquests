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
 * classes/local/step_type/base.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\local\step_type;

defined('MOODLE_INTERNAL') || die();

abstract class base implements \local_xpquests\step_type_interface {
    protected function config(\stdClass $step): array {
        if (empty($step->configjson)) {
            return [];
        }
        $decoded = json_decode($step->configjson, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function require_cmid(array $config, int $courseid): int {
        global $DB;
        $cmid = isset($config['cmid']) ? (int)$config['cmid'] : 0;
        if (!$cmid || !$DB->record_exists('course_modules', ['id' => $cmid, 'course' => $courseid])) {
            throw new \invalid_parameter_exception('Invalid course module for this quest step.');
        }
        return $cmid;
    }

    protected function get_cminfo(int $cmid): \cm_info {
        $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
        $modinfo = get_fast_modinfo($cm->course);
        return $modinfo->get_cm($cmid);
    }

    protected function availability_time(\stdClass $step, \stdClass $progress): int {
        global $DB;
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], '*', MUST_EXIST);
        if (empty($quest->sequential)) {
            return (int)$progress->startedat;
        }

        $sql = "SELECT MAX(sp.completedat)
                  FROM {local_xpquests_steps} s
             LEFT JOIN {local_xpquests_step_progress} sp
                    ON sp.stepid = s.id AND sp.progressid = :progressid
                 WHERE s.questid = :questid
                   AND s.sortorder < :sortorder
                   AND s.optional = 0";
        $max = $DB->get_field_sql($sql, [
            'progressid' => $progress->id,
            'questid' => $step->questid,
            'sortorder' => $step->sortorder,
        ]);
        return max((int)$progress->startedat, (int)$max);
    }

    public function get_progress(int $userid, \stdClass $step, \stdClass $progress): array {
        $done = $this->is_completed($userid, $step, $progress);
        return ['current' => $done ? 1.0 : 0.0, 'target' => 1.0, 'completed' => $done];
    }
}
