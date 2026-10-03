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
 * classes/integration/celebration_provider.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\integration;


/**
 * Celebration provider.
 */
class celebration_provider {
    /**
     * Queue completed.
     */
    public static function queue_completed(\stdClass $quest, \stdClass $progress): void {
        $class = '\\local_xpcelebration\\api';
        if (!class_exists($class) || !method_exists($class, 'queue')) {
            return;
        }
        try {
            $class::queue(
                (int)$progress->userid,
                (int)$quest->courseid,
                'quest_completed',
                get_string('celebration_title', 'local_xpquests'),
                get_string('celebration_message', 'local_xpquests', format_string($quest->name)),
                ['questid' => (int)$quest->id, 'progressid' => (int)$progress->id]
            );
        } catch (\Throwable $e) {
            debugging('local_xpcelebration integration failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
