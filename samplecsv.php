<?php
// This file is part of Moodle - http://moodle.org/
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
 * Download a static, non-personal sample for an authorised assignment editor.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_turnitinrubricimporter\local\target;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$cmid = required_param('cmid', PARAM_INT);
[$context, $course, $cm] = target::resolve($cmid);
require_login($course, true, $cm);
target::require_capabilities($context);
$content = "criterion,level,level_description,score\n"
    . "Clarity,Excellent,Clear and concise,10\n"
    . "Clarity,Satisfactory,Mostly clear,5\n"
    . "Clarity,Needs improvement,Lacks clarity,0\n";
send_file($content, 'rubric_import_template.csv', 0, 0, true, true, 'text/csv');
