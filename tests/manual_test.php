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
 * tests/manual_test.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests;


/**
 * Manual tests.
 *
 * @covers \\local_xpquests\\api
 */
final class manual_test extends \advanced_testcase {
    /**
     * Course.
     *
     * @var mixed
     */
    private $course;
    /**
     * Teacher.
     *
     * @var mixed
     */
    private $teacher;
    /**
     * Student.
     *
     * @var mixed
     */
    private $student;
    /**
     * Quest.
     *
     * @var mixed
     */
    private $quest;
    /**
     * Step.
     *
     * @var mixed
     */
    private $step;

    /**
     * SetUp.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->teacher = $this->getDataGenerator()->create_user();
        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, 'student');
        $now = time();
        $this->quest = (object)[
            'courseid' => $this->course->id,
            'name' => 'Manual quest',
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
        ];
        $this->quest->id = $DB->insert_record('local_xpquests_quests', $this->quest);
        $this->step = (object)[
            'questid' => $this->quest->id,
            'steptype' => 'manual',
            'name' => 'Teacher validates',
            'configjson' => '{}',
            'sortorder' => 10,
            'optional' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $this->step->id = $DB->insert_record('local_xpquests_steps', $this->step);
        $progress = (object)[
            'questid' => $this->quest->id,
            'userid' => $this->student->id,
            'status' => 'inprogress',
            'currentstep' => 0,
            'startedat' => $now,
            'completedat' => 0,
            'completioncount' => 0,
            'runnumber' => 1,
            'startxp' => 0,
            'timemodified' => $now,
        ];
        $DB->insert_record('local_xpquests_progress', $progress);
    }

    /**
     * Test authorised teacher can mark manual step.
     */
    public function test_authorised_teacher_can_mark_manual_step(): void {
        global $DB;
        $this->setUser($this->teacher);
        \local_xpquests\api::mark_manual_step($this->step->id, $this->student->id);
        $this->assertTrue($DB->record_exists('local_xpquests_step_progress', [
            'stepid' => $this->step->id,
            'userid' => $this->student->id,
        ]));
    }

    /**
     * Test user without capability cannot mark manual step.
     */
    public function test_user_without_capability_cannot_mark_manual_step(): void {
        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_xpquests\api::mark_manual_step($this->step->id, $this->student->id);
    }
}
