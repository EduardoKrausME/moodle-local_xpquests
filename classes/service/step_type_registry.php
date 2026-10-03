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
 * classes/service/step_type_registry.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\service;


/**
 * Step type registry.
 */
class step_type_registry {
    /**
     * Get map.
     */
    public static function get_map(): array {
        $types = [
            'activity_view' => \local_xpquests\local\step_type\activity_view::class,
            'activity_completion' => \local_xpquests\local\step_type\activity_completion::class,
            'quiz_attempt' => \local_xpquests\local\step_type\quiz_attempt::class,
            'quiz_pass' => \local_xpquests\local\step_type\quiz_pass::class,
            'forum_post' => \local_xpquests\local\step_type\forum_post::class,
            'assignment_submission' => \local_xpquests\local\step_type\assignment_submission::class,
            'course_section_completion' => \local_xpquests\local\step_type\course_section_completion::class,
            'xp_earned' => \local_xpquests\local\step_type\xp_earned::class,
            'manual' => \local_xpquests\local\step_type\manual::class,
        ];

        if (function_exists('get_plugins_with_function')) {
            foreach (get_plugins_with_function('xpquests_step_types', 'lib.php') as $callbacks) {
                foreach ($callbacks as $callback) {
                    $extra = $callback();
                    if (is_array($extra)) {
                        $types = array_merge($types, $extra);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Get.
     */
    public static function get(string $type): \local_xpquests\step_type_interface {
        $map = self::get_map();
        if (empty($map[$type]) || !class_exists($map[$type])) {
            throw new \coding_exception('Unknown XP Quest step type: ' . $type);
        }
        $instance = new $map[$type]();
        if (!$instance instanceof \local_xpquests\step_type_interface) {
            throw new \coding_exception('Step type must implement step_type_interface: ' . $type);
        }
        return $instance;
    }

    /**
     * Choices.
     */
    public static function choices(): array {
        $choices = [];
        foreach (self::get_map() as $key => $class) {
            if (class_exists($class)) {
                $choices[$key] = (new $class())->get_name();
            }
        }
        return $choices;
    }
}
