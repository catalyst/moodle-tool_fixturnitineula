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

use Integrations\PhpSdk\TiiUser;

/**
 * lib for fixing Turnitin EULA issues.
 *
 * @package     tool_fixturnitineula
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 function tool_fixturnitineula_fix_user($userid) {
    global $DB;
    // First check if the user has already accepted the eula.
    $u = $DB->get_record('plagiarism_turnitin_users', ['userid' => $userid]);
    if (empty($u)) {
        return "could not find a Turnitin user record for this user.";
    } else if (!empty($u->user_agreement_accepted)) {
        return 'Error: User has already accepted the eula';
    }
    $user = new turnitin_user($userid, "Learner");
    if ($user->get_accepted_user_agreement()) {
        return 'User has already accepted the eula, Moodle has been updated to correct this..';
    }
    $turnitincomms = new turnitin_comms();
    $turnitincall = $turnitincomms->initialise_api();

    $user = new TiiUser();
    $user->setUserId($userid);

    $response = $turnitincall->readUser($user);
    $readuser = $response->getUser();

    // User has not accepted - lets trigger an API call to do that for them.
    $readuser->setAcceptedUserAgreement(true);
    $response = $turnitincall->updateUser($readuser);

    $newUser = $response->getUser();
    if ($newUser->get_accepted_user_agreement()) {
        return 'The EULA for this user has now been accepted.';
    } else {
        return "An attempt to set the EULA for this user failed.";
    }
 }


/**
 * Resubmit a users submission to Turnitin when one did not previously exist.
 *
 * @param int $userid
 * @param int $cmid
 * @param int $assignid
 * @return void
 */
 function tool_fixturnitineula_resubmit_user($userid, $cmid, $assignid) {
    global $DB;
    $submissions = $DB->get_records('assign_submission', ['userid' => $userid, 'assignment' => $assignid]);
    // Get all submissions for this user in this cmid
    foreach ($submissions as $assignsubmission) {
        // Get all files for this submission
        $filesconditions = ['component' => 'assignsubmission_file',
                            'itemid' => $assignsubmission->id, 'userid' => $userid];
        if ($files = $DB->get_records('files', $filesconditions)) {
            foreach ($files as $file) {
                $at = new plagiarism_plugin_turnitin();
                $at->queue_submission_to_turnitin($cmid, $userid, $userid, $file->pathnamehash, 'file', $assignsubmission->id, 'file_uploaded');
            }
        }
    }
 }
