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
 * markmanual.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_xpquests\api;

require('../../config.php');

$stepid = required_param('stepid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
require_sesskey();

$step = $DB->get_record('local_xpquests_steps', ['id' => $stepid], '*', MUST_EXIST);
$quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/xpquests:markmanual', $context);

api::mark_manual_step($stepid, $userid);
redirect(new moodle_url('/local/xpquests/quest.php', ['id' => $quest->id, 'preview' => 1]));
