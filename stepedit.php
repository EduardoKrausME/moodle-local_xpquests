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
 * stepedit.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_xpquests\form\step_form;
use local_xpquests\service\step_type_registry;

require('../../config.php');

$questid = required_param('questid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/xpquests:managequests', $context);

$step = $id ? $DB->get_record('local_xpquests_steps', ['id' => $id, 'questid' => $questid], '*', MUST_EXIST) : null;
$PAGE->set_url('/local/xpquests/stepedit.php', ['questid' => $questid, 'id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title($id ? get_string('editstep', 'local_xpquests') : get_string('addstep', 'local_xpquests'));
$PAGE->set_heading(format_string($quest->name));

$form = new step_form(null, ['courseid' => $course->id]);
if ($step) {
    $defaults = clone $step;
    $config = json_decode($step->configjson ?: '{}', true) ?: [];
    foreach ($config as $key => $value) {
        $defaults->{$key} = $value;
    }
    $form->set_data($defaults);
} else {
    $form->set_data((object)['questid' => $questid]);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/xpquests/steps.php', ['questid' => $questid]));
}
if ($data = $form->get_data()) {
    $rawconfig = [
        'cmid' => isset($data->cmid) ? (int)$data->cmid : 0,
        'minposts' => isset($data->minposts) ? (int)$data->minposts : 1,
        'sectionnum' => isset($data->sectionnum) ? (int)$data->sectionnum : 0,
        'amount' => isset($data->amount) ? (int)$data->amount : 0,
    ];
    try {
        $type = step_type_registry::get($data->steptype);
        $config = $type->validate_configuration($rawconfig, $course->id);
    } catch (Throwable $e) {
        throw new moodle_exception('invalidstepconfig', 'local_xpquests', '', null, $e->getMessage());
    }
    $now = time();
    $record = (object)[
        'questid' => $questid,
        'steptype' => $data->steptype,
        'name' => $data->name,
        'configjson' => json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'optional' => !empty($data->optional) ? 1 : 0,
        'timemodified' => $now,
    ];
    if ($id) {
        $record->id = $id;
        $DB->update_record('local_xpquests_steps', $record);
    } else {
        $record->sortorder = 10 + (int)$DB->get_field_sql(
                'SELECT COALESCE(MAX(sortorder), 0) FROM {local_xpquests_steps} WHERE questid = :questid',
                ['questid' => $questid]
            );
        $record->timecreated = $now;
        $DB->insert_record('local_xpquests_steps', $record);
    }
    redirect(new moodle_url('/local/xpquests/steps.php', ['questid' => $questid]));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
