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
 * classes/step_type_interface.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;


use stdClass;

/**
 * Step type interface.
 */
interface step_type_interface {
    /**
     * Get name.
     */
    public function get_name(): string;

    /**
     * Validate and normalize a step configuration.
     *
     * @param array $config
     * @param int $courseid
     * @return array Normalized configuration.
     */
    public function validate_configuration(array $config, int $courseid): array;

    /**
     * Is completed.
     */
    public function is_completed(int $userid, stdClass $step, stdClass $progress): bool;

    /**
     * Get progress.
     *
     * @return array{current:float,target:float,completed:bool}
     */
    public function get_progress(int $userid, stdClass $step, stdClass $progress): array;

    /**
     * Get description.
     */
    public function get_description(stdClass $step): string;
}
