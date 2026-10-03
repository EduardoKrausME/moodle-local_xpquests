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
 * steps.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$questid = required_param('questid', PARAM_INT);
$quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/xpquests:managequests', $context);

$PAGE->set_url('/local/xpquests/steps.php', ['questid' => $questid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('managesteps', 'local_xpquests'));
$PAGE->set_heading(format_string($quest->name));
$PAGE->requires->js_call_amd('local_xpquests/steps', 'init', ['xpquests-step-list', sesskey()]);

$records = $DB->get_records('local_xpquests_steps', ['questid' => $questid], 'sortorder ASC, id ASC');
$steps = [];
foreach ($records as $step) {
    try {
        $type = \local_xpquests\service\step_type_registry::get($step->steptype);
        $description = $type->get_description($step);
        $typename = $type->get_name();
    } catch (Throwable $e) {
        $description = get_string('step_unavailable', 'local_xpquests');
        $typename = $step->steptype;
    }
    $steps[] = [
        'id' => $step->id,
        'name' => format_string($step->name),
        'typename' => $typename,
        'description' => $description,
        'optional' => !empty($step->optional),
        'editurl' => (new moodle_url('/local/xpquests/stepedit.php', ['questid' => $questid, 'id' => $step->id]))->out(false),
        'actionurl' => (new moodle_url('/local/xpquests/stepaction.php'))->out(false),
        'questid' => (int)$questid,
        'sesskey' => sesskey(),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpquests/steps_manage', [
    'questname' => format_string($quest->name),
    'questid' => (int)$questid,
    'steps' => $steps,
    'empty' => empty($steps),
    'addurl' => (new moodle_url('/local/xpquests/stepedit.php', ['questid' => $questid]))->out(false),
    'backurl' => (new moodle_url('/local/xpquests/manage.php', ['courseid' => $course->id]))->out(false),
    'previewurl' => (new moodle_url('/local/xpquests/quest.php', ['id' => $questid, 'preview' => 1]))->out(false),
]);
echo $OUTPUT->footer();
