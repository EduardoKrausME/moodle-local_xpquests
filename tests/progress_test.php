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
 * tests/progress_test.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;

use advanced_testcase;
use local_xpquests\integration\credit_provider_interface;
use local_xpquests\integration\xp_provider_interface;
use local_xpquests\service\progress_manager;
use local_xpquests\service\reward_manager;
use local_xpquests\step_type\activity_completion;
use stdClass;

defined('MOODLE_INTERNAL') || die;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses -- Test doubles share this testcase file.

/**
 * Progress tests.
 *
 * @covers \local_xpquests\service\progress_manager
 */
final class progress_test extends advanced_testcase {
    /**
     * Course.
     *
     * @var mixed
     */
    private $course;
    /**
     * Student.
     *
     * @var mixed
     */
    private $student;

    /**
     * SetUp.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, 'student');
    }

    /**
     * Test sequential order is enforced.
     */
    public function test_sequential_order_is_enforced(): void {
        global $DB;
        $quest = $this->create_quest(['sequential' => 1]);
        $first = $this->create_step($quest, 'manual', 10);
        $second = $this->create_step($quest, 'manual', 20);
        $manager = $this->manager();
        $progress = $manager->get_or_create_progress($quest, $this->student->id);

        $this->assertFalse($manager->mark_step_completed($quest, $second, $progress));
        $this->assertTrue($manager->mark_step_completed($quest, $first, $progress));
        $progress = $DB->get_record('local_xpquests_progress', ['id' => $progress->id], '*', MUST_EXIST);
        $this->assertTrue($manager->mark_step_completed($quest, $second, $progress));
        $this->assertEquals('completed', $DB->get_field('local_xpquests_progress', 'status', ['id' => $progress->id]));
    }

    /**
     * Test any order allows later step first.
     */
    public function test_any_order_allows_later_step_first(): void {
        $quest = $this->create_quest(['sequential' => 0]);
        $this->create_step($quest, 'manual', 10);
        $second = $this->create_step($quest, 'manual', 20);
        $manager = $this->manager();
        $progress = $manager->get_or_create_progress($quest, $this->student->id);
        $this->assertTrue($manager->mark_step_completed($quest, $second, $progress));
    }

    /**
     * Test optional step does not block completion.
     */
    public function test_optional_step_does_not_block_completion(): void {
        global $DB;
        $quest = $this->create_quest(['sequential' => 1]);
        $required = $this->create_step($quest, 'manual', 10, 0);
        $this->create_step($quest, 'manual', 20, 1);
        $manager = $this->manager();
        $progress = $manager->get_or_create_progress($quest, $this->student->id);
        $manager->mark_step_completed($quest, $required, $progress);
        $this->assertEquals('completed', $DB->get_field('local_xpquests_progress', 'status', ['id' => $progress->id]));
    }

    /**
     * Test removed activity remains incomplete without fatal error.
     */
    public function test_removed_activity_remains_incomplete_without_fatal_error(): void {
        $quest = $this->create_quest();
        $step = $this->create_step($quest, 'activity_completion', 10, 0, ['cmid' => 999999]);
        $progress = $this->manager()->get_or_create_progress($quest, $this->student->id);
        $type = new activity_completion();
        $this->assertFalse($type->is_completed($this->student->id, $step, $progress));
        $this->assertSame(get_string('step_activity_missing', 'local_xpquests'), $type->get_description($step));
    }

    /**
     * Test duplicate step completion is idempotent.
     */
    public function test_duplicate_step_completion_is_idempotent(): void {
        global $DB;
        $quest = $this->create_quest();
        $step = $this->create_step($quest, 'manual');
        $manager = $this->manager();
        $progress = $manager->get_or_create_progress($quest, $this->student->id);
        $this->assertTrue($manager->mark_step_completed($quest, $step, $progress));
        $this->assertFalse($manager->mark_step_completed($quest, $step, $progress));
        $this->assertEquals(1, $DB->count_records('local_xpquests_step_progress', [
            'progressid' => $progress->id,
            'stepid' => $step->id,
        ]));
    }

    /**
     * Test repeatable quest creates separate runs.
     */
    public function test_repeatable_quest_creates_separate_runs(): void {
        $quest = $this->create_quest(['repeatable' => 1, 'maxcompletions' => 2]);
        $step = $this->create_step($quest, 'manual');
        $manager = $this->manager();
        $run1 = $manager->get_or_create_progress($quest, $this->student->id);
        $manager->mark_step_completed($quest, $step, $run1);
        $run2 = $manager->get_or_create_progress($quest, $this->student->id);
        $this->assertNotEquals($run1->id, $run2->id);
        $this->assertEquals(2, $run2->runnumber);
    }

    /**
     * Test closed period cannot start and active run expires.
     */
    public function test_closed_period_cannot_start_and_active_run_expires(): void {
        global $DB;
        $quest = $this->create_quest(['timeend' => time() - 100]);
        $manager = $this->manager();
        $this->assertNull($manager->get_or_create_progress($quest, $this->student->id));

        $progress = (object)[
            'questid' => $quest->id,
            'userid' => $this->student->id,
            'status' => 'inprogress',
            'currentstep' => 0,
            'startedat' => time() - 200,
            'completedat' => 0,
            'completioncount' => 0,
            'runnumber' => 1,
            'startxp' => 0,
            'timemodified' => time() - 200,
        ];
        $progress->id = $DB->insert_record('local_xpquests_progress', $progress);
        $manager->recalculate($quest, $progress);
        $this->assertEquals('expired', $DB->get_field('local_xpquests_progress', 'status', ['id' => $progress->id]));
    }

    /**
     * Manager.
     */
    private function manager(): progress_manager {
        $xp = new fake_xp_provider();
        $credits = new fake_credit_provider();
        $rewards = new reward_manager($xp, $credits);
        return new progress_manager($rewards, $xp);
    }

    /**
     * Create quest.
     */
    private function create_quest(array $overrides = []): stdClass {
        global $DB;
        $now = time();
        $data = array_merge([
            'courseid' => $this->course->id,
            'name' => 'Quest',
            'description' => '',
            'image' => '',
            'enabled' => 1,
            'sequential' => 1,
            'timestart' => 0,
            'timeend' => 0,
            'repeatable' => 0,
            'maxcompletions' => 1,
            'rewardxp' => 0,
            'rewardcredits' => 0,
            'sortorder' => 10,
            'timecreated' => $now,
            'timemodified' => $now,
        ], $overrides);
        $quest = (object)$data;
        $quest->id = $DB->insert_record('local_xpquests_quests', $quest);
        return $quest;
    }

    /**
     * Create step.
     */
    private function create_step(
        stdClass $quest,
        string $type,
        int $sortorder = 10,
        int $optional = 0,
        array $config = []
    ): stdClass {
        global $DB;
        $now = time();
        $step = (object)[
            'questid' => $quest->id,
            'steptype' => $type,
            'name' => 'Step ' . $sortorder,
            'configjson' => json_encode($config),
            'sortorder' => $sortorder,
            'optional' => $optional,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $step->id = $DB->insert_record('local_xpquests_steps', $step);
        return $step;
    }
}

/**
 * Fake xp provider.
 */
class fake_xp_provider implements xp_provider_interface {
    /**
     * Awards.
     *
     * @var mixed
     */
    public $awards = 0;
    /**
     * Total.
     *
     * @var mixed
     */
    public $total = 0;

    /**
     * Award.
     */
    public function award(int $userid, int $courseid, int $amount, string $reference): void {
        $this->awards++;
        $this->total += $amount;
    }

    /**
     * Get total.
     */
    public function get_total(int $userid, int $courseid): int {
        return $this->total;
    }
}

/**
 * Fake credit provider.
 */
class fake_credit_provider implements credit_provider_interface {
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
