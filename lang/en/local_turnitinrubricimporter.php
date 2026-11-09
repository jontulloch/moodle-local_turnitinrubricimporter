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
 * English language strings.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acknowledgescoring'] = 'I accept generated scoring if this file is qualitative or a grading form.';
$string['acknowledgescoringrequired'] = 'Confirm that you accept the generated scoring described above before importing this file.';
$string['assignmentalreadygraded'] = 'This assignment already has recorded grades. Import into an ungraded assignment instead.';
$string['chooserubricfile'] = 'Choose a Turnitin RBC, JSON or CSV file';
$string['conversionnotice'] = 'Qualitative rubrics receive generated scores from highest to lowest, ending at zero. Turnitin grading forms become two-level Moodle rubrics (not met / met). These conversions change the scoring model. Review all imported criteria, scores and student visibility options before grading.';
$string['criterionfallback'] = 'Criterion';
$string['csvbadrow'] = 'CSV row {$a} must contain exactly four fields with a criterion and level label.';
$string['csvempty'] = 'The CSV file is empty.';
$string['csvformatheading'] = 'CSV template';
$string['csvinvalid'] = 'The CSV file could not be read.';
$string['csvmissingcolumns'] = 'The CSV file must contain exactly these unique columns: criterion, level, level_description, score.';
$string['downloadtemplatecsvlink'] = 'Download the sample CSV rubric';
$string['duplicatelevel'] = 'CSV row {$a} repeats a level label within the same criterion.';
$string['errorrepeatedscores'] = 'Every level within a criterion must have a distinct score at five decimal places.';
$string['errorrubricexists'] = 'A grading definition already exists for this assignment. Importing another one is not allowed.';
$string['eventrubricimported'] = 'Rubric imported';
$string['filerequired'] = 'Select exactly one rubric file.';
$string['filetoolarge'] = 'The rubric file exceeds the maximum size of 2 MiB.';
$string['gradingformgeneratedlevelcomplete'] = 'Criterion met';
$string['gradingformgeneratedlevelzero'] = 'Criterion not met';
$string['importbusy'] = 'Another import is in progress for this assignment. Try again shortly.';
$string['importedcsvrubricdefaultname'] = 'Imported CSV rubric';
$string['importedfromcsv'] = 'Imported from a CSV rubric';
$string['importedfromturnitin'] = 'Imported from a Turnitin {$a}';
$string['importedrubricdefaultname'] = 'Imported Turnitin rubric';
$string['importerror'] = 'The rubric could not be imported. Contact your administrator if this continues.';
$string['importfromfile'] = 'Import from Turnitin or CSV';
$string['importsuccess'] = 'Rubric imported and activated. Review its criteria, scores and visibility options before grading.';
$string['instructions'] = 'Import an exported Turnitin RBC file or a UTF-8 CSV file. The maximum file size is 2 MiB. The assignment must use a numeric grade and must have no grading definition or recorded grades. A successful import activates the new rubric, ready for use.';
$string['invalidencoding'] = 'The file must contain non-empty UTF-8 text without null bytes.';
$string['invalidname'] = 'The rubric name must contain between 1 and 255 characters.';
$string['invalidscore'] = 'Scores must be finite, non-negative numbers no greater than 99999.99999, with at most five decimal places.';
$string['invalidstructure'] = 'The file has an unsupported or inconsistent rubric structure. Check the file and its references.';
$string['invalidtarget'] = 'The requested activity, context and grading area do not match a supported Moodle Assignment.';
$string['levelfallback'] = 'Level';
$string['noticegradingform'] = 'Two-level criteria were generated for the grading form.';
$string['noticequalitative'] = 'Scores were generated for the qualitative rubric.';
$string['numericgraderequired'] = 'This assignment must use a numeric maximum grade greater than zero.';
$string['pluginname'] = 'Turnitin Rubric Importer';
$string['privacy:metadata'] = 'The plugin has no separate personal data store. Imported rubric text and staff author identifiers are held by core grading. Source uploads are held in the current user draft area and removed after successful import; unsuccessful uploads follow Moodle draft cleanup. Import events use the configured Moodle logstore. Retention, export and deletion are handled by those Moodle components. No data is sent to Turnitin or another external service.';
$string['rbcinvalidjson'] = 'The RBC file is not valid JSON or exceeds the supported nesting depth.';
$string['rbcmissingcell'] = 'A criterion is missing one or more rubric level cells.';
$string['rbctypecustom'] = 'custom rubric';
$string['rbctypegradingform'] = 'grading form';
$string['rbctypequalitative'] = 'qualitative rubric';
$string['rbctypestandard'] = 'standard rubric';
$string['rbcunsupported'] = 'The RBC scoring method is missing or unsupported. Export a supported rubric and try again.';
$string['rubriclimits'] = 'A rubric must have 1–100 criteria, each with 2–20 levels, and no more than 2000 levels in total. Descriptions must be no longer than 10000 bytes.';
$string['submitfile'] = 'Import rubric';
$string['turnitinrubricimporter:import'] = 'Import rubric files into Moodle Assignments';
$string['unsupportedfile'] = 'Only .rbc, .json and .csv rubric files are supported.';
