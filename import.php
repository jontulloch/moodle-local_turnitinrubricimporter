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
 * Upload a rubric to one authorised Moodle Assignment.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_turnitinrubricimporter\form\import_form;
use local_turnitinrubricimporter\local\importer;
use local_turnitinrubricimporter\local\target;

require(__DIR__ . '/../../config.php');

$cmid = optional_param('cmid', 0, PARAM_INT);
$areaid = optional_param('areaid', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
[$context, $course, $cm, $area] = target::resolve($cmid, $areaid, $contextid);
require_login($course, true, $cm);
target::require_capabilities($context);
$params = ['cmid' => $cm->id, 'areaid' => $areaid, 'contextid' => $contextid];
$url = new moodle_url('/local/turnitinrubricimporter/import.php', $params);
$returnurl = target::management_url($context);

$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('importfromfile', 'local_turnitinrubricimporter'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();

$form = new import_form($url, $params);
if ($form->is_cancelled()) {
    redirect($returnurl);
}
$error = '';
if ($data = $form->get_data()) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    try {
        importer::import(
            (int)$cm->id,
            $form->get_parsed_rubric(),
            sesskey(),
            !empty($data->acknowledgescoring),
            $areaid,
            $contextid
        );
        $form->delete_uploaded_file();
        redirect(
            $returnurl,
            get_string('importsuccess', 'local_turnitinrubricimporter'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        // Do not expose database diagnostics or internal paths in a normal notification.
        $error = $e->module === 'local_turnitinrubricimporter' ? $e->getMessage() :
            get_string('importerror', 'local_turnitinrubricimporter');
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importfromfile', 'local_turnitinrubricimporter'));
if ($error !== '') {
    echo $OUTPUT->notification(s($error), \core\output\notification::NOTIFY_ERROR);
}
if ($area && $DB->record_exists('grading_definitions', ['areaid' => $area->id])) {
    echo $OUTPUT->notification(
        get_string('errorrubricexists', 'local_turnitinrubricimporter'),
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->continue_button($returnurl);
} else {
    $form->display();
}
echo $OUTPUT->footer();
