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
 * classes/event_step_type_interface.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;


use core\event\base;
use stdClass;

/**
 * Event step type interface.
 */
interface event_step_type_interface extends step_type_interface {
    /**
     * Supports event.
     */
    public function supports_event(base $event): bool;

    /**
     * Whether this event targets the configured step, independently of whether
     * the completion criterion has already been reached.
     */
    public function matches_event(base $event, stdClass $step): bool;

    /**
     * Is completed by event.
     */
    public function is_completed_by_event(
        base $event,
        int $userid,
        stdClass $step,
        stdClass $progress
    ): bool;
}
