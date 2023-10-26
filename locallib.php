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
    } else if (empty($u->user_agreement_accepted)) {
        return 'Error: User has already accepted the eula';
    }
    $user = new turnitin_user($userid, "Learner");
    if ($user->get_accepted_user_agreement()) {
        return 'User has already accepted the eula, Moodle has been updated to correct this..';
    }

    // User has not accepted - lets trigger an API call to do that for them.
    $user->setAcceptedUserAgreement(true);
    $response = $api->updateUser($user);

    $newUser = $response->getUser();
    if ($newUser->get_accepted_user_agreement()) {
        return 'The EULA for this user has now been accepted.';
    } else {
        return "An attempt to set the EULA for this user failed.";
    }
 }

 function tool_fixturnitineula_resubmit_user($userid, $cmid) {

 }