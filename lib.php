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
 * Plugin library functions.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Adds quest links to the course navigation.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 * @return void
 */
function local_xpquests_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (has_capability('local/xpquests:view', $context)) {
        $navigation->add(
            get_string('pluginname', 'local_xpquests'),
            new moodle_url('/local/xpquests/index.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_xpquests'
        );
    }
    if (has_capability('local/xpquests:managequests', $context)) {
        $navigation->add(
            get_string('managequests', 'local_xpquests'),
            new moodle_url('/local/xpquests/manage.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'local_xpquests_manage'
        );
    }
}
