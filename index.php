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

$courseid = required_param('id', PARAM_INT);
$resetall = optional_param("resetall", 0, PARAM_INT);

admin_externalpage_setup('tool_fixturnitineula');

$url = new moodle_url('/admin/tool/fixturnitineula/index.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'tool_fixturnitineula'));

echo $OUTPUT->header();
$urla = new moodle_url("/admin/tool/fixturnitineula/index.php", ['id' => $courseid, "resetall" => 1]);
echo $OUTPUT->single_button($urla, "Reset all users in this course");

$coursecontext = \context_course::instance($courseid);
list($enrolsql, $params) = get_enrolled_sql($coursecontext, 'mod/assign:submit');
$sql = "SELECT * FROM ($enrolsql) enrolled_users_view 
          JOIN {plagiarism_turnitin_users} tu ON tu.userid = enrolled_users_view.id
          AND tu.user_agreement_accepted = 0";
$users = $DB->get_records_sql($sql, $params);
if ($resetall == 1) {
    $urla->param('resetall', '2');
    echo $OUTPUT->confirm("Are you sure you want to set the EULA for all users in this course?", $urla, $url);
    echo $OUTPUT->footer();
    die;
} elseif ($resetall == 2 and confirm_sesskey()) {
    $responses = [];
   foreach ($users as $user) {
       $responses[] = tool_fixturnitineula_fix_user($user->id);
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
    $url = new moodle_url("/admin/tool/fixturnitineula/accepteula.php", ['id' => $courseid, 'userid' => $user->id]);
    $actionlink = $OUTPUT->action_link($url, "FIX");
    $table->add_data(array(fullname($user), $actionlink));
}
$table->print_html();

echo $OUTPUT->footer();