<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import form for uploading a Turnitin RBC or CSV rubric file.
 */
class import_form extends moodleform {
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

        $mform->addElement('hidden', 'areaid');
        $mform->setType('areaid', PARAM_INT);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('submitfile', 'local_turnitinrubricimporter'));
        $mform->addElement('html', html_writer::end_div());
    }
}
