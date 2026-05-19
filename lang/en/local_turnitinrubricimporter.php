<?php
/**
 * Language strings for Turnitin Rubric Importer.
 *
 * @package   local_turnitinrubricimporter
 */

$string['chooserbcfile'] = 'Choose Turnitin RBC file';
$string['chooserubricfile'] = 'Choose Turnitin RBC or CSV file';
$string['choosecsvfile'] = 'Choose CSV file';
$string['criterionfallback'] = 'Criterion';
$string['local/turnitinrubricimporter:import'] = 'Allows the user to import Turnitin RBC or CSV grading rubrics into an activity.';
$string['enablemaxlevelscore'] = 'Enable maximum score validation.';
$string['enablemaxlevelscore_desc'] = 'Legacy setting retained from the original CSV importer. The Turnitin RBC importer does not use this setting.';
$string['enableminlevelscore'] = 'Enable minimum score validation.';
$string['enableminlevelscore_desc'] = 'Legacy setting retained from the original CSV importer. The Turnitin RBC importer does not use this setting.';
$string['errormaxexceeded'] = 'Error: Score {$a->score} in criterion {$a->criterion} exceeds the maximum allowed of {$a->max}.';
$string['errorminmissing'] = 'Error: Criterion {$a->criterion} does not contain the minimum allowed score of {$a->min}.';
$string['errormismatchtotal'] = 'Error: The sum of highest levels is {$a->sum}, but the activity maximum grade is {$a->grademax}.';
$string['errorrepeatedscores'] = 'Error: Criterion {$a} has repeated level scores. Each level must have a unique score.';
$string['errorrubricexists'] = 'A rubric is already defined for this activity. You cannot import another one.';
$string['importedrubricdefaultname'] = 'Imported Turnitin rubric';
$string['importedrubricname'] = 'Imported Rubric ({$a})';
$string['importerror'] = 'An error occurred while importing the rubric.';
$string['importfromcsv'] = 'Import rubric from CSV';
$string['importfromrbc'] = 'Import rubric from Turnitin RBC';
$string['importfromfile'] = 'Import from Turnitin or CSV';
$string['importfromrbc_card'] = 'Import rubric from Turnitin RBC';
$string['importfromfile_card'] = 'Import from Turnitin or CSV';
$string['importsuccess'] = 'Rubric imported successfully.';
$string['levelfallback'] = 'Level';
$string['maxlevelscore'] = 'Maximum score per level';
$string['maxlevelscore_desc'] = 'Legacy setting retained from the original CSV importer.';
$string['minlevelscore'] = 'Minimum score per level';
$string['minlevelscore_desc'] = 'Legacy setting retained from the original CSV importer.';
$string['pluginname'] = 'Turnitin Rubric Importer';
$string['privacy:metadata'] = 'This plugin does not store any personal user data.';
$string['rbcempty'] = 'The RBC file does not contain a readable rubric.';
$string['rbccriterionnoscore'] = 'Criterion "{$a}" does not have a usable weighting.';
$string['rbccriterionnotenoughlevels'] = 'Criterion "{$a}" does not contain enough usable rubric levels.';
$string['rbcinvalidjson'] = 'The selected file is not a valid Turnitin RBC JSON file.';
$string['rbclevelnoscore'] = 'Level "{$a}" does not have a usable score.';
$string['rbcmissingcell'] = 'Criterion "{$a}" is missing one or more rubric level cells.';
$string['rbcnotenoughlevels'] = 'The RBC file does not contain enough usable rubric levels.';
$string['rbcrequired'] = 'You must select a valid Turnitin RBC file.';
$string['rbcunsupported'] = 'This RBC file type is not supported. This version supports standard, custom, qualitative, and grading form Turnitin RBC exports.';
$string['rubricalreadydefined'] = 'A rubric is already defined for this activity. Importing a new one is not allowed.';
$string['submitcsv'] = 'Import rubric';
$string['submitrbc'] = 'Import Turnitin rubric';
$string['submitfile'] = 'Import rubric';
$string['rbcbuttonhelp'] = 'Upload a standard Turnitin .rbc export and create a Moodle rubric for this grading area.';
$string['filebuttonhelp'] = 'Upload a standard Turnitin .rbc export or a CSV file using columns criterion, level, level_description, score.';
$string['filerequired'] = 'You must select a valid Turnitin RBC or CSV file.';
$string['csvinvalid'] = 'The selected CSV file could not be read.';
$string['csvempty'] = 'The CSV file does not contain a readable rubric.';
$string['csvmissingcolumns'] = 'The CSV file must contain these exact columns: criterion, level, level_description, score.';
$string['csvbadrow'] = 'CSV row {$a} must contain criterion, level, level description, and a numeric score.';
$string['csvnotenoughlevels'] = 'Criterion "{$a}" does not contain enough rubric levels.';
$string['importedcsvrubricdefaultname'] = 'Imported CSV rubric';


$string['acceptedfiletypeslabel'] = 'Accepted file types:';
$string['acceptedfiletypesmanual'] = 'Turnitin .rbc files or standard .csv files.';
$string['csvformatshort'] = 'you can populate, or create your own using this exact structure:';
$string['acceptedformatshelp'] = 'Accepted file types: Turnitin .rbc exports and CSV files using the exact column structure shown below.';
$string['csvformatheading'] = 'CSV format example';
$string['downloadsamplecsv'] = 'Download sample CSV';
$string['downloadtemplatecsvlink'] = 'Click here to download a template csv file';
$string['dropzoneprompt'] = 'Click here, or drag files here to upload them';
$string['detectedrubrictype'] = 'Detected rubric type: {$a}.';
$string['filetypecsv'] = 'CSV rubric';
$string['gradingformgeneratedlevelcomplete'] = 'Criterion met';
$string['gradingformgeneratedlevelzero'] = 'Criterion not met';
$string['importedfromcsv'] = 'Imported from a CSV rubric';
$string['importedfromturnitin'] = 'Imported from a Turnitin {$a}';
$string['noticegradingform'] = 'You uploaded a Turnitin Grading Form which does not map directly to a standard Moodle rubric. Criterion levels were automatically generated to create a compatible Moodle rubric.';
$string['noticequalitative'] = 'This qualitative rubric had no numeric scores. Moodle scores were generated automatically.';
$string['rbctypecustom'] = 'Custom Rubric';
$string['rbctypegradingform'] = 'Grading Form';
$string['rbctypequalitative'] = 'Qualitative Rubric';
$string['rbctypestandard'] = 'Standard Rubric';
