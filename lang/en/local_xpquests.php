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
 * Language strings for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activity'] = 'Activity';
$string['addstep'] = 'Add step';
$string['back'] = 'Back';
$string['cannotdeletestepwithhistory'] = 'This step already has learner progress and cannot be deleted.';
$string['celebration_message'] = 'You completed the quest “{$a}”.';
$string['celebration_title'] = 'Quest completed';
$string['chooseactivity'] = 'Choose an activity';
$string['copyof'] = 'Copy of {$a}';
$string['createquest'] = 'Create quest';
$string['credits'] = 'credits';
$string['delete'] = 'Delete';
$string['description'] = 'Description';
$string['disabled'] = 'Disabled';
$string['dragtoorder'] = 'Drag to reorder';
$string['duplicate'] = 'Duplicate';
$string['edit'] = 'Edit';
$string['editquest'] = 'Edit quest';
$string['editstep'] = 'Edit step';
$string['enabled'] = 'Enabled';
$string['error_maxcompletions'] = 'Maximum completions cannot be negative.';
$string['error_negative_reward'] = 'Rewards cannot be negative.';
$string['error_timeend'] = 'The end date must be after the start date.';
$string['event_quest_completed'] = 'Quest completed';
$string['event_quest_started'] = 'Quest started';
$string['event_quest_step_completed'] = 'Quest step completed';
$string['imageurl'] = 'Image URL';
$string['invalidstepconfig'] = 'Invalid step configuration: {$a}';
$string['managequests'] = 'Manage quests';
$string['managesteps'] = 'Manage quest steps';
$string['maxcompletions'] = 'Maximum completions (0 = unlimited)';
$string['minposts'] = 'Required posts';
$string['mode'] = 'Mode';
$string['mode_anyorder'] = 'Any order';
$string['mode_sequential'] = 'Sequential';
$string['noquests'] = 'No quests have been created yet.';
$string['noquestsavailable'] = 'There are no quests available right now.';
$string['nosteps'] = 'This quest has no steps yet.';
$string['of'] = 'of';
$string['optional'] = 'Optional';
$string['optionalstep'] = 'Optional step';
$string['personalxpapiunavailable'] = 'The required local_personalxp public API is unavailable.';
$string['personalxpdisabled'] = 'Personal XP is disabled, so the quest XP reward cannot be delivered yet.';
$string['pluginname'] = 'XP Quests';
$string['preview'] = 'Preview';
$string['privacy:metadata:progress'] = 'Quest execution progress for each learner.';
$string['privacy:metadata:progress:completedat'] = 'When the quest execution was completed.';
$string['privacy:metadata:progress:questid'] = 'The quest being performed.';
$string['privacy:metadata:progress:startedat'] = 'When the quest execution started.';
$string['privacy:metadata:progress:status'] = 'The current execution status.';
$string['privacy:metadata:progress:userid'] = 'The user whose quest progress is stored.';
$string['privacy:metadata:rewards'] = 'Idempotent reward delivery records linked to quest executions.';
$string['privacy:metadata:rewards:amount'] = 'The reward amount.';
$string['privacy:metadata:rewards:progressid'] = 'The quest execution associated with the reward.';
$string['privacy:metadata:rewards:rewardtype'] = 'The kind of reward, such as XP or credits.';
$string['privacy:metadata:rewards:status'] = 'The reward delivery status.';
$string['privacy:metadata:stepprogress'] = 'Quest steps completed by a learner.';
$string['privacy:metadata:stepprogress:completedat'] = 'When the step was completed.';
$string['privacy:metadata:stepprogress:stepid'] = 'The completed quest step.';
$string['privacy:metadata:stepprogress:userid'] = 'The user who completed the step.';
$string['quest'] = 'Quest';
$string['questcompleted'] = 'Quest completed. Rewards are processed once for this run.';
$string['questexpired'] = 'This quest run has expired.';
$string['questname'] = 'Quest name';
$string['questnotactive'] = 'This quest is not active for the selected user.';
$string['repeatable'] = 'Repeatable quest';
$string['requiredsteps'] = 'required steps';
$string['rewardcredits'] = 'Credit reward';
$string['rewardlockfailed'] = 'Unable to acquire the reward processing lock.';
$string['rewards'] = 'Rewards';
$string['rewardshopapiunavailable'] = 'The local_rewardshop public API is unavailable.';
$string['rewardxp'] = 'XP reward';
$string['section'] = 'Course section';
$string['sequential'] = 'Sequential mode';
$string['status'] = 'Status';
$string['status_completed'] = 'Completed';
$string['status_expired'] = 'Expired';
$string['status_inprogress'] = 'In progress';
$string['status_notstarted'] = 'Not started';
$string['step_activity_missing'] = 'The configured activity was removed or is unavailable.';
$string['step_unavailable'] = 'This step can no longer be evaluated.';
$string['stepdesc_activity_completion'] = 'Complete “{$a}”.';
$string['stepdesc_activity_view'] = 'View “{$a}”.';
$string['stepdesc_assignment_submission'] = 'Submit “{$a}”.';
$string['stepdesc_course_section_completion'] = 'Complete all activities with completion tracking in section {$a}.';
$string['stepdesc_forum_post'] = 'Publish {$a->count} post(s) in “{$a->name}”.';
$string['stepdesc_manual'] = 'This step is confirmed manually by an authorised teacher.';
$string['stepdesc_quiz_attempt'] = 'Submit an attempt in “{$a}”.';
$string['stepdesc_quiz_pass'] = 'Reach the configured passing grade in “{$a}”.';
$string['stepdesc_xp_earned'] = 'Earn {$a} XP after starting this quest.';
$string['stepname'] = 'Step name';
$string['steps'] = 'Steps';
$string['steptype'] = 'Step type';
$string['steptype_activity_completion'] = 'Activity completed';
$string['steptype_activity_view'] = 'Activity viewed';
$string['steptype_assignment_submission'] = 'Assignment submitted';
$string['steptype_course_section_completion'] = 'Course section completed';
$string['steptype_forum_post'] = 'Forum post';
$string['steptype_manual'] = 'Manual confirmation';
$string['steptype_quiz_attempt'] = 'Quiz attempted';
$string['steptype_quiz_pass'] = 'Quiz passed';
$string['steptype_xp_earned'] = 'XP earned';
$string['studentview'] = 'Student view';
$string['task_reconcile_active_quests'] = 'Reconcile active XP Quests';
$string['timeend'] = 'Available until';
$string['timestart'] = 'Available from';
$string['toggle'] = 'Enable/disable';
$string['xpamount'] = 'XP to earn after the quest starts';
$string['xpquests:managequests'] = 'Create and manage quests';
$string['xpquests:markmanual'] = 'Mark manual quest steps';
$string['xpquests:view'] = 'View personal quests';
$string['xprewardlabel'] = 'XP Quest completion reward';
