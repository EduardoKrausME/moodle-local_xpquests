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
 * manage.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/xpquests:managequests', $context);

$PAGE->set_url('/local/xpquests/manage.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('managequests', 'local_xpquests'));
$PAGE->set_heading(format_string($course->fullname));

$records = $DB->get_records('local_xpquests_quests', ['courseid' => $courseid], 'sortorder ASC, id ASC');
$quests = [];
foreach ($records as $quest) {
    $quests[] = [
        'id' => $quest->id,
        'name' => format_string($quest->name),
        'enabled' => !empty($quest->enabled),
        'mode' => $quest->sequential ? get_string('mode_sequential', 'local_xpquests') : get_string('mode_anyorder', 'local_xpquests'),
        'steps' => $DB->count_records('local_xpquests_steps', ['questid' => $quest->id]),
        'rewardxp' => (int)$quest->rewardxp,
        'rewardcredits' => (int)$quest->rewardcredits,
        'editurl' => (new moodle_url('/local/xpquests/edit.php', ['courseid' => $courseid, 'id' => $quest->id]))->out(false),
        'stepsurl' => (new moodle_url('/local/xpquests/steps.php', ['questid' => $quest->id]))->out(false),
        'previewurl' => (new moodle_url('/local/xpquests/quest.php', ['id' => $quest->id, 'preview' => 1]))->out(false),
        'actionurl' => (new moodle_url('/local/xpquests/action.php'))->out(false),
        'courseid' => (int)$courseid,
        'sesskey' => sesskey(),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpquests/manage', [
    'quests' => $quests,
    'empty' => empty($quests),
    'createurl' => (new moodle_url('/local/xpquests/edit.php', ['courseid' => $courseid]))->out(false),
    'studenturl' => (new moodle_url('/local/xpquests/index.php', ['courseid' => $courseid]))->out(false),
]);
echo $OUTPUT->footer();
