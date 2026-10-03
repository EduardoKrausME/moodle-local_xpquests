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
 * classes/local/step_type/manual.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\local\step_type;

defined('MOODLE_INTERNAL') || die();

class manual extends base {
    public function get_name(): string {
        return get_string('steptype_manual', 'local_xpquests');
    }

    public function validate_configuration(array $config, int $courseid): array {
        return [];
    }

    public function is_completed(int $userid, \stdClass $step, \stdClass $progress): bool {
        global $DB;
        return $DB->record_exists('local_xpquests_step_progress', [
            'progressid' => $progress->id,
            'stepid' => $step->id,
            'userid' => $userid,
            'status' => 'completed',
        ]);
    }

    public function get_description(\stdClass $step): string {
        return get_string('stepdesc_manual', 'local_xpquests');
    }
}
