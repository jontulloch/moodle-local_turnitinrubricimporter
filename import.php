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

/**
 * Turnitin RBC and CSV importer for Moodle rubric grading definitions.
 *
 * @package   local_turnitinrubricimporter
 */

require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

$areaid     = required_param('areaid', PARAM_INT);
$contextid  = required_param('contextid', PARAM_INT);
$returnurl  = required_param('returnurl', PARAM_LOCALURL);

$context = context::instance_by_id($contextid, MUST_EXIST);
list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, true, $cm);
require_capability('moodle/grade:managegradingforms', $context);

$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/turnitinrubricimporter/import.php', [
    'areaid' => $areaid,
    'contextid' => $contextid,
    'returnurl' => $returnurl,
]));
$PAGE->set_title(get_string('importfromfile', 'local_turnitinrubricimporter'));
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('local_turnitinrubricimporter_import');
if (!empty($PAGE->activityheader) && method_exists($PAGE->activityheader, 'disable')) {
    $PAGE->activityheader->disable();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importfromfile', 'local_turnitinrubricimporter'));

$exists = $DB->record_exists('grading_definitions', [
    'areaid' => $areaid,
    'method' => 'rubric',
]);

if ($exists) {
    echo $OUTPUT->notification(get_string('errorrubricexists', 'local_turnitinrubricimporter'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

/**
 * Form to upload a Turnitin RBC or CSV rubric file.
 */
class rubric_import_form extends moodleform {
    /**
     * Define form elements.
     */
    public function definition() {
        $mform = $this->_form;
        $sampleurl = new moodle_url('/local/turnitinrubricimporter/samplecsv.php');

        $layoutcss = <<<'HTML'
<style>
body.local_turnitinrubricimporter_import .activity-header,
body.local_turnitinrubricimporter_import [data-region="activity-information"],
body.local_turnitinrubricimporter_import .activity-information,
body.local_turnitinrubricimporter_import .activity-dates {
    display: none !important;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_import_wrap {
    max-width: 900px;
    margin-top: 1.5rem;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_import_wrap p {
    font-size: 1rem;
    line-height: 1.5;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_template_link {
    text-decoration: underline;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_csv_example {
    white-space: pre-wrap;
    margin-bottom: 0;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_import_wrap .fdescription.required {
    display: none;
}
</style>
HTML;
        $mform->addElement('html', $layoutcss);
        $mform->addElement('html', html_writer::start_div('local_turnitinrubricimporter_import_wrap'));

        $introtext = get_string('acceptedfiletypesmanual', 'local_turnitinrubricimporter');
        $mform->addElement('html', html_writer::tag('p', $introtext, ['class' => 'mb-3 text-muted']));

        $mform->addElement(
            'filepicker',
            'rubricfile',
            get_string('chooserubricfile', 'local_turnitinrubricimporter'),
            null,
            [
                'accepted_types' => '*',
                'maxbytes' => 0,
                'subdirs' => 0,
            ]
        );
        $mform->addRule('rubricfile', null, 'required');

        $csvhelp = html_writer::tag(
            'p',
            html_writer::link(
                $sampleurl,
                get_string('downloadtemplatecsvlink', 'local_turnitinrubricimporter'),
                ['class' => 'local_turnitinrubricimporter_template_link']
            ) . ' ' . get_string('csvformatshort', 'local_turnitinrubricimporter'),
            ['class' => 'mb-2 text-muted']
        );

        $csvhelp .= html_writer::tag(
            'pre',
            'criterion,level,level_description,score' . PHP_EOL .
            '"Clarity","1","Clear and concise","10"' . PHP_EOL .
            '"Clarity","2","Mostly clear","5"' . PHP_EOL .
            '"Clarity","3","Lacks clarity","0"',
            ['class' => 'local_turnitinrubricimporter_csv_example mt-2 mb-3 p-3 bg-light border rounded']
        );

        $mform->addElement('html', html_writer::div($csvhelp, 'local_turnitinrubricimporter_csv_help mt-2'));

        $mform->addElement('hidden', 'areaid', $this->_customdata['areaid']);
        $mform->setType('areaid', PARAM_INT);

        $mform->addElement('hidden', 'contextid', $this->_customdata['contextid']);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']);
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('submitfile', 'local_turnitinrubricimporter'));
        $mform->addElement('html', html_writer::end_div());
    }
}

/**
 * Return a safely sorted copy of records by position then num then id.
 *
 * @param array $records
 * @return array
 */
function local_turnitinrubricimporter_sort_turnitin_records(array $records): array {
    usort($records, static function($a, $b): int {
        $ap = isset($a['position']) ? (float)$a['position'] : 999999;
        $bp = isset($b['position']) ? (float)$b['position'] : 999999;
        if ($ap < $bp) {
            return -1;
        }
        if ($ap > $bp) {
            return 1;
        }

        $an = isset($a['num']) ? (float)$a['num'] : 999999;
        $bn = isset($b['num']) ? (float)$b['num'] : 999999;
        if ($an < $bn) {
            return -1;
        }
        if ($an > $bn) {
            return 1;
        }

        $ai = isset($a['id']) ? (float)$a['id'] : 0;
        $bi = isset($b['id']) ? (float)$b['id'] : 0;
        return $ai <=> $bi;
    });

    return $records;
}

/**
 * Convert Turnitin rubric text to plain Moodle-safe text.
 *
 * @param string $text
 * @return string
 */
function local_turnitinrubricimporter_rbc_plain_text(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text);
    $text = strip_tags($text);
    $text = preg_replace('/\s*\/\s*(strong|b|em|i|u|p|br|div|span)\s*\/?\s*/i', ' ', $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\s*\n\s*/', "\n", $text);
    return trim($text);
}

/**
 * Get a readable Turnitin rubric type from the scoring method and file shape.
 *
 * @param array $turnitinrubric
 * @param array $decoded
 * @return string
 */
function local_turnitinrubricimporter_detect_rbc_type(array $turnitinrubric, array $decoded): string {
    $method = isset($turnitinrubric['scoring_method']) ? (int)$turnitinrubric['scoring_method'] : -1;

    if ($method === 5 || empty($decoded['RubricScale']) || empty($decoded['RubricCriterionScale'])) {
        return 'gradingform';
    }

    if ($method === 0) {
        return 'qualitative';
    }

    if ($method === 4) {
        return 'custom';
    }

    return 'standard';
}

/**
 * Return the display name for a detected Turnitin rubric type.
 *
 * @param string $type
 * @return string
 */
function local_turnitinrubricimporter_rbc_type_label(string $type): string {
    switch ($type) {
        case 'custom':
            return get_string('rbctypecustom', 'local_turnitinrubricimporter');
        case 'qualitative':
            return get_string('rbctypequalitative', 'local_turnitinrubricimporter');
        case 'gradingform':
            return get_string('rbctypegradingform', 'local_turnitinrubricimporter');
        case 'standard':
        default:
            return get_string('rbctypestandard', 'local_turnitinrubricimporter');
    }
}

/**
 * Build evenly spaced generated scores for non-numeric qualitative imports.
 *
 * @param int $count
 * @return array
 */
function local_turnitinrubricimporter_generated_scores(int $count): array {
    $scores = [];
    if ($count <= 1) {
        return [0];
    }

    for ($i = 0; $i < $count; $i++) {
        $scores[] = $count - 1 - $i;
    }

    return $scores;
}

/**
 * Convert a Turnitin RBC JSON export to the internal rubric array used by this importer.
 *
 * Supports standard rubrics, custom rubrics, qualitative rubrics, and grading forms.
 *
 * @param string $content
 * @return array
 * @throws moodle_exception
 */
function local_turnitinrubricimporter_parse_rbc(string $content): array {
    $decoded = json_decode($content, true);

    if (!is_array($decoded)) {
        throw new moodle_exception('rbcinvalidjson', 'local_turnitinrubricimporter');
    }

    if (!isset($decoded['Rubric']) || !is_array($decoded['Rubric']) || empty($decoded['Rubric'])) {
        throw new moodle_exception('rbcunsupported', 'local_turnitinrubricimporter');
    }

    if (!isset($decoded['RubricCriterion']) || !is_array($decoded['RubricCriterion']) || empty($decoded['RubricCriterion'])) {
        throw new moodle_exception('rbcempty', 'local_turnitinrubricimporter');
    }

    $turnitinrubric = reset($decoded['Rubric']);
    $type = local_turnitinrubricimporter_detect_rbc_type($turnitinrubric, $decoded);
    $typelabel = local_turnitinrubricimporter_rbc_type_label($type);

    $rubricname = isset($turnitinrubric['name']) && trim((string)$turnitinrubric['name']) !== ''
        ? clean_param(trim((string)$turnitinrubric['name']), PARAM_TEXT)
        : get_string('importedrubricdefaultname', 'local_turnitinrubricimporter');

    $criteria = local_turnitinrubricimporter_sort_turnitin_records($decoded['RubricCriterion']);

    if ($type === 'gradingform') {
        $rubric = [];

        foreach ($criteria as $criterion) {
            $criterionname = isset($criterion['name']) && trim((string)$criterion['name']) !== ''
                ? clean_param(trim((string)$criterion['name']), PARAM_TEXT)
                : get_string('criterionfallback', 'local_turnitinrubricimporter');

            $criteriondescription = isset($criterion['description'])
                ? local_turnitinrubricimporter_rbc_plain_text((string)$criterion['description'])
                : '';

            $criteriontext = $criterionname;
            if (trim($criteriondescription) !== '') {
                $criteriontext .= "\n" . $criteriondescription;
            }

            $criterionvalue = isset($criterion['value']) && (float)$criterion['value'] > 0
                ? (float)$criterion['value']
                : 1.0;

            $rubric[$criteriontext] = [
                [
                    'definition' => get_string('gradingformgeneratedlevelzero', 'local_turnitinrubricimporter'),
                    'score' => 0,
                ],
                [
                    'definition' => get_string('gradingformgeneratedlevelcomplete', 'local_turnitinrubricimporter'),
                    'score' => $criterionvalue,
                ],
            ];
        }

        if (empty($rubric)) {
            throw new moodle_exception('rbcempty', 'local_turnitinrubricimporter');
        }

        return [
            'name' => $rubricname,
            'rubric' => $rubric,
            'type' => $type,
            'typelabel' => $typelabel,
            'description' => get_string('importedfromturnitin', 'local_turnitinrubricimporter', $typelabel),
            'notices' => [
                get_string('noticegradingform', 'local_turnitinrubricimporter'),
            ],
        ];
    }

    foreach (['RubricCriterionScale', 'RubricScale'] as $requiredkey) {
        if (!isset($decoded[$requiredkey]) || !is_array($decoded[$requiredkey])) {
            throw new moodle_exception('rbcunsupported', 'local_turnitinrubricimporter');
        }
    }

    if (empty($decoded['RubricScale'])) {
        throw new moodle_exception('rbcnotenoughlevels', 'local_turnitinrubricimporter');
    }

    $scales = local_turnitinrubricimporter_sort_turnitin_records($decoded['RubricScale']);

    $criterionscalesbycriterion = [];
    foreach ($decoded['RubricCriterionScale'] as $cell) {
        if (!isset($cell['criterion']) || !isset($cell['scale_value'])) {
            continue;
        }
        $criterionid = (string)$cell['criterion'];
        $scaleid = (string)$cell['scale_value'];
        $criterionscalesbycriterion[$criterionid][$scaleid] = $cell;
    }

    $scaleids = [];
    foreach ($scales as $scale) {
        if (!isset($scale['id'])) {
            continue;
        }
        $scaleid = (string)$scale['id'];

        $hastext = false;
        foreach ($criteria as $criterion) {
            $criterionid = isset($criterion['id']) ? (string)$criterion['id'] : '';
            if ($criterionid === '') {
                continue;
            }
            $cell = $criterionscalesbycriterion[$criterionid][$scaleid] ?? null;
            $description = is_array($cell) && isset($cell['description']) ? trim((string)$cell['description']) : '';
            if ($description !== '') {
                $hastext = true;
                break;
            }
        }

        $scalename = isset($scale['name']) ? strtolower(trim((string)$scale['name'])) : '';
        $isemptyscale = ($scalename === 'empty scale' || $scalename === '');

        if ($hastext || !$isemptyscale) {
            $scaleids[] = $scaleid;
        }
    }

    if (count($scaleids) < 2) {
        throw new moodle_exception('rbcnotenoughlevels', 'local_turnitinrubricimporter');
    }

    $scalesbyid = [];
    foreach ($scales as $scale) {
        if (isset($scale['id'])) {
            $scalesbyid[(string)$scale['id']] = $scale;
        }
    }

    $generatedscores = local_turnitinrubricimporter_generated_scores(count($scaleids));
    $rubric = [];
    $usedgeneratedscores = false;

    foreach ($criteria as $criterion) {
        if (!isset($criterion['id'])) {
            continue;
        }

        $criterionid = (string)$criterion['id'];
        $criterionname = isset($criterion['name']) && trim((string)$criterion['name']) !== ''
            ? clean_param(trim((string)$criterion['name']), PARAM_TEXT)
            : get_string('criterionfallback', 'local_turnitinrubricimporter');

        $criteriondescription = isset($criterion['description'])
            ? local_turnitinrubricimporter_rbc_plain_text((string)$criterion['description'])
            : '';
        if (trim($criteriondescription) !== '') {
            $criteriontext = $criterionname . "\n" . $criteriondescription;
        } else {
            $criteriontext = $criterionname;
        }

        $criterionvalue = isset($criterion['value']) ? (float)$criterion['value'] : 0.0;

        $levels = [];
        $levelindex = 0;
        foreach ($scaleids as $scaleid) {
            if (!isset($scalesbyid[$scaleid])) {
                continue;
            }
            $scale = $scalesbyid[$scaleid];
            $cell = $criterionscalesbycriterion[$criterionid][$scaleid] ?? null;

            if (!is_array($cell)) {
                throw new moodle_exception('rbcmissingcell', 'local_turnitinrubricimporter', '', $criterionname);
            }

            $scalename = isset($scale['name']) && trim((string)$scale['name']) !== ''
                ? clean_param(trim((string)$scale['name']), PARAM_TEXT)
                : get_string('levelfallback', 'local_turnitinrubricimporter');

            $description = isset($cell['description'])
                ? local_turnitinrubricimporter_rbc_plain_text((string)$cell['description'])
                : '';
            if (trim($description) !== '') {
                $definition = $scalename . "\n" . $description;
            } else {
                $definition = $scalename;
            }

            $cellvalue = isset($cell['value']) ? (float)$cell['value'] : null;
            $scalevalue = isset($scale['value']) ? (float)$scale['value'] : null;

            if ($type === 'qualitative') {
                if ($cellvalue !== null && $cellvalue > 0) {
                    $score = $cellvalue;
                } else {
                    $score = $generatedscores[$levelindex] ?? 0;
                    $usedgeneratedscores = true;
                }
            } else if ($cellvalue !== null && $cellvalue > 0) {
                $score = $cellvalue;
            } else if ($criterionvalue > 0 && $scalevalue !== null) {
                $score = round($criterionvalue * $scalevalue / 100, 4);
            } else if ($scalevalue !== null && $scalevalue > 0) {
                $score = $scalevalue;
            } else {
                $score = $generatedscores[$levelindex] ?? 0;
                $usedgeneratedscores = true;
            }

            $levels[] = [
                'definition' => $definition,
                'score' => $score,
            ];

            $levelindex++;
        }

        if (count($levels) < 2) {
            throw new moodle_exception('rbccriterionnotenoughlevels', 'local_turnitinrubricimporter', '', $criterionname);
        }

        $rubric[$criteriontext] = $levels;
    }

    if (empty($rubric)) {
        throw new moodle_exception('rbcempty', 'local_turnitinrubricimporter');
    }

    $notices = [];
    if ($type === 'qualitative' && $usedgeneratedscores) {
        $notices[] = get_string('noticequalitative', 'local_turnitinrubricimporter');
    }

    return [
        'name' => $rubricname,
        'rubric' => $rubric,
        'type' => $type,
        'typelabel' => $typelabel,
        'description' => get_string('importedfromturnitin', 'local_turnitinrubricimporter', $typelabel),
        'notices' => $notices,
    ];
}


/**
 * Convert supported CSV rubric content to the internal rubric array used by this importer.
 *
 * Required CSV columns:
 * criterion,level,level_description,score
 *
 * @param string $content
 * @param string $filename
 * @return array
 * @throws moodle_exception
 */
function local_turnitinrubricimporter_parse_csv(string $content, string $filename = ''): array {
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        throw new moodle_exception('csvinvalid', 'local_turnitinrubricimporter');
    }

    fwrite($handle, $content);
    rewind($handle);

    $header = fgetcsv($handle);
    if ($header === false || !is_array($header)) {
        fclose($handle);
        throw new moodle_exception('csvempty', 'local_turnitinrubricimporter');
    }

    $columns = [];
    foreach ($header as $index => $name) {
        $normalised = strtolower(trim((string)$name));
        $normalised = preg_replace('/^\xEF\xBB\xBF/', '', $normalised);
        $columns[$normalised] = $index;
    }

    $required = ['criterion', 'level', 'level_description', 'score'];
    foreach ($required as $requiredcolumn) {
        if (!array_key_exists($requiredcolumn, $columns)) {
            fclose($handle);
            throw new moodle_exception('csvmissingcolumns', 'local_turnitinrubricimporter');
        }
    }

    $rubric = [];
    $rownumber = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $rownumber++;

        if (!is_array($row) || count(array_filter($row, static function($value) {
            return trim((string)$value) !== '';
        })) === 0) {
            continue;
        }

        $criterion = isset($row[$columns['criterion']])
            ? local_turnitinrubricimporter_rbc_plain_text((string)$row[$columns['criterion']])
            : '';
        $level = isset($row[$columns['level']])
            ? local_turnitinrubricimporter_rbc_plain_text((string)$row[$columns['level']])
            : '';
        $description = isset($row[$columns['level_description']])
            ? local_turnitinrubricimporter_rbc_plain_text((string)$row[$columns['level_description']])
            : '';
        $scoretext = isset($row[$columns['score']]) ? trim((string)$row[$columns['score']]) : '';

        if ($criterion === '' || $level === '' || $scoretext === '' || !is_numeric($scoretext)) {
            fclose($handle);
            throw new moodle_exception('csvbadrow', 'local_turnitinrubricimporter', '', $rownumber);
        }

        $definition = $level;
        if ($description !== '') {
            $definition .= "\n" . $description;
        }

        if (!isset($rubric[$criterion])) {
            $rubric[$criterion] = [];
        }

        $rubric[$criterion][] = [
            'definition' => $definition,
            'score' => (float)$scoretext,
        ];
    }

    fclose($handle);

    if (empty($rubric)) {
        throw new moodle_exception('csvempty', 'local_turnitinrubricimporter');
    }

    foreach ($rubric as $criterion => $levels) {
        if (count($levels) < 2) {
            throw new moodle_exception('csvnotenoughlevels', 'local_turnitinrubricimporter', '', $criterion);
        }
    }

    $rubricname = get_string('importedcsvrubricdefaultname', 'local_turnitinrubricimporter');
    if ($filename !== '') {
        $rubricname = get_string('importedrubricname', 'local_turnitinrubricimporter', clean_param($filename, PARAM_FILE));
    }

    return [
        'name' => $rubricname,
        'rubric' => $rubric,
        'type' => 'csv',
        'typelabel' => get_string('filetypecsv', 'local_turnitinrubricimporter'),
        'description' => get_string('importedfromcsv', 'local_turnitinrubricimporter'),
        'notices' => [],
    ];
}

/**
 * Parse an uploaded rubric file by file type and content.
 *
 * @param string $content
 * @param string $filename
 * @return array
 * @throws moodle_exception
 */
function local_turnitinrubricimporter_parse_uploaded_rubric(string $content, string $filename = ''): array {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        return local_turnitinrubricimporter_parse_csv($content, $filename);
    }

    if ($extension === 'rbc' || $extension === 'json') {
        return local_turnitinrubricimporter_parse_rbc($content);
    }

    $trimmed = ltrim($content);
    if ($trimmed !== '' && $trimmed[0] === '{') {
        return local_turnitinrubricimporter_parse_rbc($content);
    }

    return local_turnitinrubricimporter_parse_csv($content, $filename);
}


/**
 * Return the Moodle grading definition status used for a ready form.
 *
 * @return int
 */
function local_turnitinrubricimporter_ready_status(): int {
    if (class_exists('gradingform_controller') && defined('gradingform_controller::DEFINITION_STATUS_READY')) {
        return gradingform_controller::DEFINITION_STATUS_READY;
    }

    return 20;
}

$mform = new rubric_import_form(null, [
    'areaid' => $areaid,
    'contextid' => $contextid,
    'returnurl' => $returnurl,
]);

$dataerror = false;

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($mform->is_submitted() && $mform->is_validated() && ($data = $mform->get_data())) {
    $draftitemid = file_get_submitted_draft_itemid('rubricfile');
    file_prepare_draft_area($draftitemid, $contextid, 'local_turnitinrubricimporter', 'temp', 0);

    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);

    if (empty($files)) {
        echo $OUTPUT->notification(get_string('filerequired', 'local_turnitinrubricimporter'), 'notifyproblem');
        $dataerror = true;
    } else {
        $file = reset($files);
        $content = $file->get_content();

        try {
            $parsed = local_turnitinrubricimporter_parse_uploaded_rubric($content, $file->get_filename());
            $rubric = $parsed['rubric'];
            $rubricname = $parsed['name'];
            $rubricdescription = $parsed['description'] ?? '';
            $rubricnotices = $parsed['notices'] ?? [];
            $rubrictypelabel = $parsed['typelabel'] ?? '';
        } catch (moodle_exception $e) {
            echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
            $dataerror = true;
        }

        if (!$dataerror) {
            foreach ($rubric as $criterion => $levels) {
                $scores = array_column($levels, 'score');

                if (count(array_unique($scores)) < count($scores)) {
                    $msg = get_string(
                        'errorrepeatedscores',
                        'local_turnitinrubricimporter',
                        strip_tags($criterion)
                    );
                    echo $OUTPUT->notification($msg, 'notifyproblem');
                    $dataerror = true;
                    break;
                }
            }
        }

        if (!$dataerror) {
            $definition = new stdClass();
            $definition->areaid = $areaid;
            $definition->method = 'rubric';
            $definition->name = $rubricname;
            $definition->description = $rubricdescription;
            $definition->descriptionformat = FORMAT_PLAIN;
            $definition->status = local_turnitinrubricimporter_ready_status();
            $definition->timecreated = time();
            $definition->timemodified = time();
            $definition->usercreated = $USER->id;
            $definition->usermodified = $USER->id;
            $definition->options = json_encode([
                'sortlevelsasc' => '0',
                'lockzeropoints' => '1',
                'alwaysshowdefinition' => '1',
                'showdescriptionteacher' => null,
                'showdescriptionstudent' => '1',
                'showscoreteacher' => '1',
                'showscorestudent' => '1',
                'enableremarks' => '1',
                'showremarksstudent' => '1',
            ]);
            $definitionid = $DB->insert_record('grading_definitions', $definition);

            $criteriaorder = 0;
            foreach ($rubric as $criteriontext => $levels) {
                $criterion = new stdClass();
                $criterion->definitionid = $definitionid;
                $criterion->description = $criteriontext;
                $criterion->descriptionformat = FORMAT_PLAIN;
                $criterion->sortorder = $criteriaorder++;
                $criterionid = $DB->insert_record('gradingform_rubric_criteria', $criterion);

                $levelsorder = 0;
                foreach ($levels as $level) {
                    $levelobj = new stdClass();
                    $levelobj->criterionid = $criterionid;
                    $levelobj->definition = $level['definition'];
                    $levelobj->definitionformat = FORMAT_PLAIN;
                    $levelobj->score = $level['score'];
                    $levelobj->sortorder = $levelsorder++;
                    $DB->insert_record('gradingform_rubric_levels', $levelobj);
                }
            }

            if (!empty($rubrictypelabel)) {
                echo $OUTPUT->notification(
                    get_string('detectedrubrictype', 'local_turnitinrubricimporter', $rubrictypelabel),
                    'info'
                );
            }

            if (!empty($rubricnotices)) {
                foreach ($rubricnotices as $rubricnotice) {
                    echo $OUTPUT->notification($rubricnotice, 'info');
                }
            }

            echo $OUTPUT->notification(
                get_string('importsuccess', 'local_turnitinrubricimporter'),
                'notifysuccess'
            );
            $manageurl = new moodle_url('/grade/grading/manage.php', ['areaid' => $areaid]);
            echo $OUTPUT->continue_button($manageurl);
        }
    }
}

if (!isset($data) || $dataerror) {
    $mform->display();
}

echo $OUTPUT->footer();
