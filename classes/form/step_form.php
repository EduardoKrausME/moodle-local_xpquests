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
 * classes/form/step_form.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class step_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $courseid = (int)$this->_customdata['courseid'];
        $modinfo = get_fast_modinfo($courseid);
        $modules = [0 => get_string('chooseactivity', 'local_xpquests')];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->deletioninprogress) {
                $modules[$cm->id] = format_string($cm->name) . ' (' . $cm->modname . ')';
            }
        }
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $sections[$section->section] = get_section_name($courseid, $section->section);
        }

        $mform->addElement('text', 'name', get_string('stepname', 'local_xpquests'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement('select', 'steptype', get_string('steptype', 'local_xpquests'),
            \local_xpquests\service\step_type_registry::choices());
        $mform->addElement('advcheckbox', 'optional', get_string('optionalstep', 'local_xpquests'));

        $mform->addElement('select', 'cmid', get_string('activity', 'local_xpquests'), $modules);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('text', 'minposts', get_string('minposts', 'local_xpquests'), ['size' => 8]);
        $mform->setType('minposts', PARAM_INT);
        $mform->setDefault('minposts', 1);
        $mform->addElement('select', 'sectionnum', get_string('section', 'local_xpquests'), $sections);
        $mform->setType('sectionnum', PARAM_INT);
        $mform->addElement('text', 'amount', get_string('xpamount', 'local_xpquests'), ['size' => 8]);
        $mform->setType('amount', PARAM_INT);

        $mform->hideIf('cmid', 'steptype', 'eq', 'course_section_completion');
        $mform->hideIf('cmid', 'steptype', 'eq', 'xp_earned');
        $mform->hideIf('cmid', 'steptype', 'eq', 'manual');
        $mform->hideIf('minposts', 'steptype', 'neq', 'forum_post');
        $mform->hideIf('sectionnum', 'steptype', 'neq', 'course_section_completion');
        $mform->hideIf('amount', 'steptype', 'neq', 'xp_earned');

        $mform->addElement('hidden', 'questid');
        $mform->setType('questid', PARAM_INT);
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons();
    }
}
