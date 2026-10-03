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
 * classes/local/step_type/xp_earned.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\local\step_type;


/**
 * Xp earned.
 */
class xp_earned extends base {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('steptype_xp_earned', 'local_xpquests');
    }

    /**
     * Validate configuration.
     */
    public function validate_configuration(array $config, int $courseid): array {
        $amount = max(1, (int)($config['amount'] ?? 0));
        return ['amount' => $amount];
    }

    /**
     * Current total.
     */
    private function current_total(int $userid, int $courseid): int {
        return \local_xpquests\integration\personalxp_provider::get_total_static($userid, $courseid);
    }

    /**
     * Is completed.
     */
    public function is_completed(int $userid, \stdClass $step, \stdClass $progress): bool {
        $config = $this->config($step);
        $quest = $GLOBALS['DB']->get_record('local_xpquests_quests', ['id' => $step->questid], 'courseid', MUST_EXIST);
        return max(0, $this->current_total($userid, (int)$quest->courseid) - (int)$progress->startxp)
            >= max(1, (int)($config['amount'] ?? 1));
    }

    /**
     * Get progress.
     */
    public function get_progress(int $userid, \stdClass $step, \stdClass $progress): array {
        global $DB;
        $config = $this->config($step);
        $quest = $DB->get_record('local_xpquests_quests', ['id' => $step->questid], 'courseid', MUST_EXIST);
        $current = max(0, $this->current_total($userid, (int)$quest->courseid) - (int)$progress->startxp);
        $target = max(1, (int)($config['amount'] ?? 1));
        return ['current' => (float)$current, 'target' => (float)$target, 'completed' => $current >= $target];
    }

    /**
     * Get description.
     */
    public function get_description(\stdClass $step): string {
        $config = $this->config($step);
        return get_string('stepdesc_xp_earned', 'local_xpquests', max(1, (int)($config['amount'] ?? 1)));
    }
}
