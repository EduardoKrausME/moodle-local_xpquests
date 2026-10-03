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
 * db/events.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\course_module_viewed',
        'callback' => '\\local_xpquests\\service\\event_processor::observe',
    ],
    [
        'eventname' => '\\core\\event\\course_module_completion_updated',
        'callback' => '\\local_xpquests\\service\\event_processor::observe',
    ],
    [
        'eventname' => '\\mod_quiz\\event\\attempt_submitted',
        'callback' => '\\local_xpquests\\service\\event_processor::observe',
    ],
    [
        'eventname' => '\\mod_forum\\event\\post_created',
        'callback' => '\\local_xpquests\\service\\event_processor::observe',
    ],
    [
        'eventname' => '\\mod_assign\\event\\assessable_submitted',
        'callback' => '\\local_xpquests\\service\\event_processor::observe',
    ],
];
