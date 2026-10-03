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
 * reorder.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$questid = required_param('questid', PARAM_INT);
$order = required_param_array('order', PARAM_INT);
require_sesskey();

$quest = $DB->get_record('local_xpquests_quests', ['id' => $questid], '*', MUST_EXIST);
$course = get_course($quest->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/xpquests:managequests', $context);

$expected = array_keys($DB->get_records('local_xpquests_steps', ['questid' => $questid], '', 'id'));
sort($expected);
$submitted = array_values(array_unique(array_map('intval', $order)));
$sortedsubmitted = $submitted;
sort($sortedsubmitted);
if ($expected !== $sortedsubmitted) {
    throw new invalid_parameter_exception('Invalid step order.');
}

$transaction = $DB->start_delegated_transaction();
$sortorder = 10;
foreach ($submitted as $stepid) {
    $DB->set_field('local_xpquests_steps', 'sortorder', $sortorder, ['id' => $stepid, 'questid' => $questid]);
    $DB->set_field('local_xpquests_steps', 'timemodified', time(), ['id' => $stepid, 'questid' => $questid]);
    $sortorder += 10;
}
$transaction->allow_commit();

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
