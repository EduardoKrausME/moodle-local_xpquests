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
 * classes/event/quest_completed.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\event;


use context_course;
use core\event\base;
use moodle_url;
use stdClass;

/**
 * Quest completed.
 */
class quest_completed extends base {
    /**
     * Init.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_xpquests_progress';
    }

    /**
     * Create for progress.
     */
    public static function create_for_progress(stdClass $quest, stdClass $progress): self {
        return self::create([
            'context' => context_course::instance($quest->courseid),
            'objectid' => $progress->id,
            'relateduserid' => $progress->userid,
            'other' => [
                'questid' => $quest->id,
                'runnumber' => $progress->runnumber,
                'completioncount' => $progress->completioncount,
            ],
        ]);
    }

    /**
     * Get name.
     */
    public static function get_name(): string {
        return get_string('event_quest_completed', 'local_xpquests');
    }

    /**
     * Get description.
     */
    public function get_description(): string {
        return "The user with id '{$this->relateduserid}' "
            . "completed quest '{$this->other['questid']}' "
            . "run '{$this->other['runnumber']}'.";
    }

    /**
     * Get url.
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/xpquests/quest.php', ['id' => $this->other['questid']]);
    }
}
