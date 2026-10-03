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
 * tests/reward_test.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;

use advanced_testcase;
use local_xpquests\integration\credit_provider_interface;
use local_xpquests\integration\xp_provider_interface;
use local_xpquests\service\reward_manager;

defined('MOODLE_INTERNAL') || die;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses -- Test doubles share this testcase file.

/**
 * Reward tests.
 *
 * @covers \local_xpquests\service\reward_manager
 */
final class reward_test extends advanced_testcase {
    /**
     * Test xp and credits are delivered once.
     */
    public function test_xp_and_credits_are_delivered_once(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $quest = (object)[
            'courseid' => $course->id,
            'name' => 'Rewards',
            'description' => '',
            'image' => '',
            'enabled' => 1,
            'sequential' => 1,
            'timestart' => 0,
            'timeend' => 0,
            'repeatable' => 0,
            'maxcompletions' => 1,
            'rewardxp' => 300,
            'rewardcredits' => 50,
            'sortorder' => 10,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $quest->id = $DB->insert_record('local_xpquests_quests', $quest);
        $progress = (object)[
            'questid' => $quest->id,
            'userid' => $user->id,
            'status' => 'completed',
            'currentstep' => 10,
            'startedat' => $now,
            'completedat' => $now,
            'completioncount' => 1,
            'runnumber' => 1,
            'startxp' => 0,
            'timemodified' => $now,
        ];
        $progress->id = $DB->insert_record('local_xpquests_progress', $progress);

        $xp = new reward_fake_xp_provider();
        $credits = new reward_fake_credit_provider();
        $manager = new reward_manager($xp, $credits);
        $manager->deliver($quest, $progress);
        $manager->deliver($quest, $progress);

        $this->assertEquals(1, $xp->awards);
        $this->assertEquals(1, $credits->awards);
        $this->assertEquals(2, $DB->count_records('local_xpquests_rewards', ['progressid' => $progress->id]));
        $this->assertEquals(2, $DB->count_records('local_xpquests_rewards', [
            'progressid' => $progress->id,
            'status' => 'delivered',
        ]));
    }
}

/**
 * Reward fake xp provider.
 */
class reward_fake_xp_provider implements xp_provider_interface {
    /**
     * Awards.
     *
     * @var mixed
     */
    public $awards = 0;

    /**
     * Award.
     */
    public function award(int $userid, int $courseid, int $amount, string $reference): void {
        $this->awards++;
    }

    /**
     * Get total.
     */
    public function get_total(int $userid, int $courseid): int {
        return 0;
    }
}

/**
 * Reward fake credit provider.
 */
class reward_fake_credit_provider implements credit_provider_interface {
    /**
     * Awards.
     *
     * @var mixed
     */
    public $awards = 0;

    /**
     * Is available.
     */
    public function is_available(): bool {
        return true;
    }

    /**
     * Add.
     */
    public function add(int $userid, int $courseid, int $amount, string $reference): void {
        $this->awards++;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
