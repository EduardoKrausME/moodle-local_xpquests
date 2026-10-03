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
 * classes/integration/personalxp_provider.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\integration;

defined('MOODLE_INTERNAL') || die();

class personalxp_provider implements xp_provider_interface {
    public function award(int $userid, int $courseid, int $amount, string $reference): void {
        if ($amount <= 0) {
            return;
        }
        $class = '\\local_personalxp\\service\\xp_manager';
        if (!class_exists($class) || !method_exists($class, 'award')) {
            throw new \moodle_exception('personalxpapiunavailable', 'local_xpquests');
        }
        if (method_exists($class, 'is_enabled') && !$class::is_enabled()) {
            throw new \moodle_exception('personalxpdisabled', 'local_xpquests');
        }

        $objectid = self::reference_id($reference);
        $class::award(
            $userid,
            $courseid,
            'local_xpquests',
            $objectid,
            $amount,
            get_string('xprewardlabel', 'local_xpquests'),
            'local_xpquests',
            \local_xpquests\event\quest_completed::class
        );
    }

    public function get_total(int $userid, int $courseid): int {
        return self::get_total_static($userid, $courseid);
    }

    public static function get_total_static(int $userid, int $courseid): int {
        $class = '\\local_personalxp\\service\\xp_manager';
        if (!class_exists($class) || !method_exists($class, 'get_total')) {
            return 0;
        }
        return (int)$class::get_total($userid, $courseid);
    }

    private static function reference_id(string $reference): int {
        if (preg_match('/(\\d+)$/', $reference, $matches)) {
            return (int)$matches[1];
        }
        return abs((int)crc32($reference));
    }
}
