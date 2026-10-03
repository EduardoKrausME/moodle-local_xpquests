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
 * stepaction.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$questid = required_param('questid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
require_sesskey();

$quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/xpquests:managequests', $context);
$step = $DB->get_record('local_xpquests_steps', ['id' => $id, 'questid' => $questid], '*', MUST_EXIST);

if ($action === 'delete') {
    if ($DB->record_exists('local_xpquests_step_progress', ['stepid' => $id])) {
        throw new moodle_exception('cannotdeletestepwithhistory', 'local_xpquests');
    }
    $DB->delete_records('local_xpquests_steps', ['id' => $id]);
}
redirect(new moodle_url('/local/xpquests/steps.php', ['questid' => $questid]));
