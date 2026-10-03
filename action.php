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
 * action.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
require_sesskey();

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/xpquests:managequests', $context);
$quest = $DB->get_record('local_xpquests_quests', ['id' => $id, 'courseid' => $courseid], '*', MUST_EXIST);

if ($action === 'toggle') {
    $DB->set_field('local_xpquests_quests', 'enabled', empty($quest->enabled) ? 1 : 0, ['id' => $id]);
    $DB->set_field('local_xpquests_quests', 'timemodified', time(), ['id' => $id]);
} else if ($action === 'duplicate') {
    $transaction = $DB->start_delegated_transaction();
    $copy = clone $quest;
    unset($copy->id);
    $copy->name = get_string('copyof', 'local_xpquests', $quest->name);
    $copy->enabled = 0;
    $copy->sortorder = 10 + (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {local_xpquests_quests} WHERE courseid = :courseid',
            ['courseid' => $courseid]
        );
    $copy->timecreated = time();
    $copy->timemodified = time();
    $newid = $DB->insert_record('local_xpquests_quests', $copy);
    $steps = $DB->get_records('local_xpquests_steps', ['questid' => $id], 'sortorder ASC, id ASC');
    foreach ($steps as $step) {
        unset($step->id);
        $step->questid = $newid;
        $step->timecreated = time();
        $step->timemodified = time();
        $DB->insert_record('local_xpquests_steps', $step);
    }
    $transaction->allow_commit();
}

redirect(new moodle_url('/local/xpquests/manage.php', ['courseid' => $courseid]));
