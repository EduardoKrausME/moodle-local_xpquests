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
 * edit.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/xpquests:managequests', $context);

if ($id) {
    $quest = $DB->get_record('local_xpquests_quests', ['id' => $id, 'courseid' => $courseid], '*', MUST_EXIST);
} else {
    $quest = null;
}

$PAGE->set_url('/local/xpquests/edit.php', ['courseid' => $courseid, 'id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title($id ? get_string('editquest', 'local_xpquests') : get_string('createquest', 'local_xpquests'));
$PAGE->set_heading(format_string($course->fullname));

$form = new \local_xpquests\form\quest_form();
if ($quest) {
    $defaults = clone $quest;
    $defaults->description_editor = ['text' => $quest->description ?: '', 'format' => FORMAT_HTML];
    $form->set_data($defaults);
} else {
    $form->set_data((object)['courseid' => $courseid]);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/xpquests/manage.php', ['courseid' => $courseid]));
}
if ($data = $form->get_data()) {
    $now = time();
    $record = (object)[
        'courseid' => $courseid,
        'name' => $data->name,
        'description' => $data->description_editor['text'] ?? '',
        'image' => $data->image ?? '',
        'enabled' => !empty($data->enabled) ? 1 : 0,
        'sequential' => !empty($data->sequential) ? 1 : 0,
        'timestart' => (int)$data->timestart,
        'timeend' => (int)$data->timeend,
        'repeatable' => !empty($data->repeatable) ? 1 : 0,
        'maxcompletions' => !empty($data->repeatable) ? max(0, (int)$data->maxcompletions) : 1,
        'rewardxp' => max(0, (int)$data->rewardxp),
        'rewardcredits' => max(0, (int)$data->rewardcredits),
        'timemodified' => $now,
    ];
    if ($id) {
        $record->id = $id;
        $DB->update_record('local_xpquests_quests', $record);
    } else {
        $record->sortorder = 10 + (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {local_xpquests_quests} WHERE courseid = :courseid',
            ['courseid' => $courseid]
        );
        $record->timecreated = $now;
        $id = $DB->insert_record('local_xpquests_quests', $record);
    }
    redirect(new moodle_url('/local/xpquests/steps.php', ['questid' => $id]));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
