<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * quest.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_xpquests\api;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$preview = optional_param('preview', 0, PARAM_BOOL);
$quest = $DB->get_record('local_xpquests_quests', ['id' => $id], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);

if ($preview) {
    require_capability('local/xpquests:managequests', $context);
} else {
    require_capability('local/xpquests:view', $context);
}

$PAGE->set_url('/local/xpquests/quest.php', ['id' => $id, 'preview' => $preview]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($quest->name));
$PAGE->set_heading(format_string($course->fullname));

if ($preview) {
    $data = api::get_quest_progress($id, $USER->id, false);
} else {
    $data = api::get_quest_progress($id, $USER->id, true);
}
$data['backurl'] = (new moodle_url('/local/xpquests/index.php', ['courseid' => $course->id]))->out(false);
$data['preview'] = (bool)$preview;
$data['hasxp'] = $data['rewardxp'] > 0;
$data['hascredits'] = $data['rewardcredits'] > 0;
$data['complete'] = $data['status'] === 'completed';
$data['expired'] = $data['status'] === 'expired';
foreach ($data['steps'] as &$step) {
    $step['icon'] = $step['completed'] ? '✓' : ($step['locked'] ? '🔒' : '○');
    $step['optional_label'] = $step['optional'] ? get_string('optional', 'local_xpquests') : '';
}
unset($step);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpquests/quest_detail', $data);
echo $OUTPUT->footer();
