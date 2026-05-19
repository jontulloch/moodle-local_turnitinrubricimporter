<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Sample CSV download for the Turnitin Rubric Importer.
 *
 * @package   local_turnitinrubricimporter
 */

require('../../config.php');

require_login();

$filename = 'rubric_import_template.csv';
$content = "criterion,level,level_description,score\n"
    . "\"Clarity\",\"1\",\"Clear and concise\",\"10\"\n"
    . "\"Clarity\",\"2\",\"Mostly clear\",\"5\"\n"
    . "\"Clarity\",\"3\",\"Lacks clarity\",\"0\"\n";

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $content;
exit;
