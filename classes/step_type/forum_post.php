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
 * classes/local/step_type/forum_post.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\step_type;


use local_xpquests\event_step_type_interface;
use mod_forum\event\post_created;
use stdClass;
use Throwable;

/**
 * Forum post.
 */
class forum_post extends base implements event_step_type_interface {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('steptype_forum_post', 'local_xpquests');
    }

    /**
     * Validate configuration.
     */
    public function validate_configuration(array $config, int $courseid): array {
        $cmid = $this->require_cmid($config, $courseid);
        get_coursemodule_from_id('forum', $cmid, $courseid, false, MUST_EXIST);
        return [
            'cmid' => $cmid,
            'minposts' => max(1, (int)($config['minposts'] ?? 1)),
        ];
    }

    /**
     * Count posts.
     */
    private function count_posts(int $userid, stdClass $step, stdClass $progress): int {
        global $DB;
        $config = $this->config($step);
        $cm = get_coursemodule_from_id('forum', (int)($config['cmid'] ?? 0), 0, false, IGNORE_MISSING);
        if (!$cm) {
            return 0;
        }
        $sql = "SELECT COUNT(p.id)
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                 WHERE d.forum = :forumid
                   AND p.userid = :userid
                   AND p.created >= :mintime";
        return (int)$DB->count_records_sql($sql, [
            'forumid' => $cm->instance,
            'userid' => $userid,
            'mintime' => $this->availability_time($step, $progress),
        ]);
    }

    /**
     * Is completed.
     */
    public function is_completed(int $userid, stdClass $step, stdClass $progress): bool {
        $config = $this->config($step);
        return $this->count_posts($userid, $step, $progress) >= max(1, (int)($config['minposts'] ?? 1));
    }

    /**
     * Get progress.
     */
    public function get_progress(int $userid, stdClass $step, stdClass $progress): array {
        $config = $this->config($step);
        $target = max(1, (int)($config['minposts'] ?? 1));
        $current = $this->count_posts($userid, $step, $progress);
        return ['current' => (float)$current, 'target' => (float)$target, 'completed' => $current >= $target];
    }

    /**
     * Supports event.
     */
    public function supports_event(\core\event\base $event): bool {
        return $event instanceof post_created;
    }

    /**
     * Matches event.
     */
    public function matches_event(\core\event\base $event, stdClass $step): bool {
        $config = $this->config($step);
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0);
    }

    /**
     * Is completed by event.
     */
    public function is_completed_by_event(
        \core\event\base $event,
        int $userid,
        stdClass $step,
        stdClass $progress
    ): bool {
        $config = $this->config($step);
        return $this->supports_event($event)
            && (int)$event->contextinstanceid === (int)($config['cmid'] ?? 0)
            && (int)$event->userid === $userid
            && $this->is_completed($userid, $step, $progress);
    }

    /**
     * Get description.
     */
    public function get_description(stdClass $step): string {
        $config = $this->config($step);
        try {
            $cm = $this->get_cminfo((int)($config['cmid'] ?? 0));
            $a = (object)['name' => $cm->name, 'count' => max(1, (int)($config['minposts'] ?? 1))];
            return get_string('stepdesc_forum_post', 'local_xpquests', $a);
        } catch (Throwable $e) {
            return get_string('step_activity_missing', 'local_xpquests');
        }
    }
}
