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
 * classes/privacy/provider.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_user_data_provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_xpquests_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'questid' => 'privacy:metadata:progress:questid',
            'status' => 'privacy:metadata:progress:status',
            'startedat' => 'privacy:metadata:progress:startedat',
            'completedat' => 'privacy:metadata:progress:completedat',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('local_xpquests_step_progress', [
            'userid' => 'privacy:metadata:stepprogress:userid',
            'stepid' => 'privacy:metadata:stepprogress:stepid',
            'completedat' => 'privacy:metadata:stepprogress:completedat',
        ], 'privacy:metadata:stepprogress');
        $collection->add_database_table('local_xpquests_rewards', [
            'progressid' => 'privacy:metadata:rewards:progressid',
            'rewardtype' => 'privacy:metadata:rewards:rewardtype',
            'amount' => 'privacy:metadata:rewards:amount',
            'status' => 'privacy:metadata:rewards:status',
        ], 'privacy:metadata:rewards');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_xpquests_quests} q ON q.courseid = ctx.instanceid
                  JOIN {local_xpquests_progress} p ON p.questid = q.id
                 WHERE ctx.contextlevel = :contextlevel AND p.userid = :userid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $sql = "SELECT p.*, q.name AS questname
                      FROM {local_xpquests_progress} p
                      JOIN {local_xpquests_quests} q ON q.id = p.questid
                     WHERE p.userid = :userid AND q.courseid = :courseid
                  ORDER BY p.startedat ASC";
            $progress = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $context->instanceid]);
            $export = [];
            foreach ($progress as $run) {
                $steps = $DB->get_records_sql(
                    "SELECT sp.*, s.name AS stepname
                       FROM {local_xpquests_step_progress} sp
                       JOIN {local_xpquests_steps} s ON s.id = sp.stepid
                      WHERE sp.progressid = :progressid
                   ORDER BY sp.completedat ASC",
                    ['progressid' => $run->id]
                );
                $rewards = $DB->get_records('local_xpquests_rewards', ['progressid' => $run->id], 'rewardtype ASC');
                $export[] = (object)[
                    'quest' => $run->questname,
                    'status' => $run->status,
                    'run' => (int)$run->runnumber,
                    'completioncount' => (int)$run->completioncount,
                    'started' => transform::datetime($run->startedat),
                    'completed' => $run->completedat ? transform::datetime($run->completedat) : null,
                    'steps' => array_map(function($step) {
                        return (object)[
                            'name' => $step->stepname,
                            'completed' => transform::datetime($step->completedat),
                        ];
                    }, array_values($steps)),
                    'rewards' => array_map(function($reward) {
                        return (object)[
                            'type' => $reward->rewardtype,
                            'amount' => (int)$reward->amount,
                            'status' => $reward->status,
                        ];
                    }, array_values($rewards)),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_xpquests')],
                (object)['quests' => $export]
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_course) {
            return;
        }
        $questids = $DB->get_fieldset_select('local_xpquests_quests', 'id', 'courseid = :courseid', [
            'courseid' => $context->instanceid,
        ]);
        self::delete_by_questids($questids, null);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $questids = $DB->get_fieldset_select('local_xpquests_quests', 'id', 'courseid = :courseid', [
                'courseid' => $context->instanceid,
            ]);
            self::delete_by_questids($questids, $userid);
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $sql = "SELECT p.userid
                  FROM {local_xpquests_progress} p
                  JOIN {local_xpquests_quests} q ON q.id = p.questid
                 WHERE q.courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $questids = $DB->get_fieldset_select('local_xpquests_quests', 'id', 'courseid = :courseid', [
            'courseid' => $context->instanceid,
        ]);
        foreach ($userlist->get_userids() as $userid) {
            self::delete_by_questids($questids, (int)$userid);
        }
    }

    private static function delete_by_questids(array $questids, ?int $userid): void {
        global $DB;
        if (!$questids) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($questids, SQL_PARAMS_NAMED, 'q');
        $where = "questid {$insql}";
        if ($userid !== null) {
            $where .= ' AND userid = :userid';
            $params['userid'] = $userid;
        }
        $progressids = $DB->get_fieldset_select('local_xpquests_progress', 'id', $where, $params);
        if (!$progressids) {
            return;
        }
        list($progresssql, $progressparams) = $DB->get_in_or_equal($progressids, SQL_PARAMS_NAMED, 'p');
        $DB->delete_records_select('local_xpquests_rewards', "progressid {$progresssql}", $progressparams);
        $DB->delete_records_select('local_xpquests_step_progress', "progressid {$progresssql}", $progressparams);
        $DB->delete_records_select('local_xpquests_progress', "id {$progresssql}", $progressparams);
    }
}
