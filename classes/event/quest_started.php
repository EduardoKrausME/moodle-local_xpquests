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
 * classes/event/quest_started.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\event;


/**
 * Quest started.
 */
class quest_started extends \core\event\base {
    /**
     * Init.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_xpquests_progress';
    }

    /**
     * Create for progress.
     */
    public static function create_for_progress(\stdClass $quest, \stdClass $progress): self {
        return self::create([
            'context' => \context_course::instance($quest->courseid),
            'objectid' => $progress->id,
            'relateduserid' => $progress->userid,
            'other' => ['questid' => $quest->id, 'runnumber' => $progress->runnumber],
        ]);
    }

    /**
     * Get name.
     */
    public static function get_name(): string {
        return get_string('event_quest_started', 'local_xpquests');
    }

    /**
     * Get description.
     */
    public function get_description(): string {
        return "The user with id '{$this->relateduserid}' started quest '{$this->other['questid']}' "\n            . "run '{$this->other['runnumber']}'.";
    }

    /**
     * Get url.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/xpquests/quest.php', ['id' => $this->other['questid']]);
    }
}
