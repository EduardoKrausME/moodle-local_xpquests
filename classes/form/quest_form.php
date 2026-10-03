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
 * classes/form/quest_form.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Quest form.
 */
class quest_form extends \moodleform {
    /**
     * Definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('questname', 'local_xpquests'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'description_editor', get_string('description', 'local_xpquests'), null, ['maxfiles' => 0]);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addElement('url', 'image', get_string('imageurl', 'local_xpquests'), ['size' => 60]);
        $mform->setType('image', PARAM_URL);

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_xpquests'));
        $mform->setDefault('enabled', 1);
        $mform->addElement('selectyesno', 'sequential', get_string('sequential', 'local_xpquests'));
        $mform->setDefault('sequential', 1);

        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'local_xpquests'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeend', get_string('timeend', 'local_xpquests'), ['optional' => true]);

        $mform->addElement('advcheckbox', 'repeatable', get_string('repeatable', 'local_xpquests'));
        $mform->addElement('text', 'maxcompletions', get_string('maxcompletions', 'local_xpquests'), ['size' => 8]);
        $mform->setType('maxcompletions', PARAM_INT);
        $mform->setDefault('maxcompletions', 1);
        $mform->disabledIf('maxcompletions', 'repeatable', 'notchecked');

        $mform->addElement('text', 'rewardxp', get_string('rewardxp', 'local_xpquests'), ['size' => 8]);
        $mform->setType('rewardxp', PARAM_INT);
        $mform->setDefault('rewardxp', 0);
        $mform->addElement('text', 'rewardcredits', get_string('rewardcredits', 'local_xpquests'), ['size' => 8]);
        $mform->setType('rewardcredits', PARAM_INT);
        $mform->setDefault('rewardcredits', 0);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons();
    }

    /**
     * Validation.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!empty($data['timeend']) && !empty($data['timestart']) && $data['timeend'] <= $data['timestart']) {
            $errors['timeend'] = get_string('error_timeend', 'local_xpquests');
        }
        if (!empty($data['repeatable']) && (int)$data['maxcompletions'] < 0) {
            $errors['maxcompletions'] = get_string('error_maxcompletions', 'local_xpquests');
        }
        if ((int)$data['rewardxp'] < 0 || (int)$data['rewardcredits'] < 0) {
            $errors['rewardxp'] = get_string('error_negative_reward', 'local_xpquests');
        }
        return $errors;
    }
}
