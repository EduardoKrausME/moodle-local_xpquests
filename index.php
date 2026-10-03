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
 * index.php for local_xpquests.
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
require_capability('local/xpquests:view', $context);

$PAGE->set_url('/local/xpquests/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_xpquests'));
$PAGE->set_heading(format_string($course->fullname));

$quests = \local_xpquests\api::get_available_quests($courseid, $USER->id);
foreach ($quests as &$quest) {
    $quest['url'] = (new moodle_url('/local/xpquests/quest.php', ['id' => $quest['id']]))->out(false);
    $quest['statuslabel'] = get_string('status_' . $quest['status'], 'local_xpquests');
    $quest['hascredits'] = $quest['rewardcredits'] > 0;
    $quest['hasxp'] = $quest['rewardxp'] > 0;
}
unset($quest);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpquests/quest_list', [
    'quests' => $quests,
    'empty' => empty($quests),
]);
echo $OUTPUT->footer();
