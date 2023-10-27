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
 * Fix Turnitin EULA issues.
 *
 * @package     tool_fixturnitineula
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');

$courseid = required_param('id', PARAM_INT);
$resetall = optional_param("resetall", 0, PARAM_INT);

admin_externalpage_setup('tool_fixturnitineula');

$url = new moodle_url('/admin/tool/fixturnitineula/submissions.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'tool_fixturnitineula'));

echo $OUTPUT->header();
$urla = new moodle_url("/admin/tool/fixturnitineula/submissions.php", ['id' => $courseid, "resetall" => 1]);
echo $OUTPUT->single_button($urla, "Resubmit submissions for all users in this course");

// Find all the users that have a submission record in this course but do not have a plagiarism files record.
$sql = "SELECT asub.id, asub.userid, asubm.assignment, asub.status, cm.id as cmid  
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
          JOIN {assign} a ON a.id = cm.instance
          JOIN {assign_submissions} asub on asub.assignment = a.id
          JOIN {plagiarism_turnitin_config} pc ON pc.cm = cm.id AND pc.name = 'turnitin_assignid' AND pc.value <> ''
     LEFT JOIN {plagiarism_turnitin_files} pf ON pf.userid = asub.userid AND pf.cm = cm.id
         WHERE cm.course = :courseid AND pf.id is null";
$params = ['courseid' => $courseid];

$users = $DB->get_records_sql($sql, $params);
if ($resetall == 1) {
    $urla->param('resetall', '2');
    echo $OUTPUT->confirm("Are you sure you want to resubmit for all users in this course?", $urla, $url);
    echo $OUTPUT->footer();
    die;
} elseif ($resetall == 2 and confirm_sesskey()) {
    $responses = [];
   foreach ($users as $user) {
       $responses[] = tool_fixturnitineula_resubmit_user($user->userid, $user->cmid, $user->assignment);
   }
   echo "All users have been reset";
}

$table = new flexible_table('fixturnitineula');
$table->define_columns(array('name', 'action'));
$table->define_headers(array(get_string('name'), get_string('action')));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'fixturnitineula');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

foreach($users as $user) {
    $ur = $DB->get_record("user", ['id' => $user->userid]);
    $url = new moodle_url("/admin/tool/fixturnitineula/resubmit.php", ['cmid' => $user->cmid, 'userid' => $user->userid, 'assign' => $user->assignment, 'course' => $courseid]);
    $actionlink = $OUTPUT->action_link($url, "FIX");
    $table->add_data(array(fullname($ur), $actionlink));
}
$table->print_html();

echo $OUTPUT->footer();