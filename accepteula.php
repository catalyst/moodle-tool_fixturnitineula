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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/plagiarism/turnitin/lib.php');
require_once($CFG->dirroot.'/admin/tool/fixturnitineula/locallib.php');

$courseid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

admin_externalpage_setup('tool_fixturnitineula');

$url = new moodle_url('/admin/tool/fixturnitineula/index.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'tool_fixturnitineula'));

$response = tool_fixturnitineula_fix_user($userid);
redirect($url, $response);
