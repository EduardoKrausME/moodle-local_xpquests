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
 * classes/task/reconcile_active_quests.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\task;

defined('MOODLE_INTERNAL') || die();

class reconcile_active_quests extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_reconcile_active_quests', 'local_xpquests');
    }

    public function execute(): void {
        global $DB;
        $manager = new \local_xpquests\service\progress_manager();
        $rs = $DB->get_recordset('local_xpquests_progress', ['status' => 'inprogress'], 'timemodified ASC');
        $processed = 0;
        foreach ($rs as $progress) {
            if ($processed >= 1000) {
                break;
            }
            $quest = $DB->get_record('local_xpquests_quests', ['id' => $progress->questid]);
            if ($quest) {
                $manager->recalculate($quest, $progress);
            }
            $processed++;
        }
        $rs->close();

        // Retry completed runs only when a configured reward is missing or not delivered.
        $sql = "SELECT DISTINCT p.*
                  FROM {local_xpquests_progress} p
                  JOIN {local_xpquests_quests} q ON q.id = p.questid
             LEFT JOIN {local_xpquests_rewards} rx
                    ON rx.progressid = p.id AND rx.rewardtype = 'xp'
             LEFT JOIN {local_xpquests_rewards} rc
                    ON rc.progressid = p.id AND rc.rewardtype = 'credits'
                 WHERE p.status = 'completed'
                   AND ((q.rewardxp > 0 AND (rx.id IS NULL OR rx.status <> 'delivered'))
                     OR (q.rewardcredits > 0 AND (rc.id IS NULL OR rc.status <> 'delivered')))";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $progress) {
            $quest = $DB->get_record('local_xpquests_quests', ['id' => $progress->questid]);
            if ($quest) {
                $manager->recalculate($quest, $progress);
            }
        }
        $rs->close();
    }
}
