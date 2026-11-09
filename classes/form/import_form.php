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

namespace local_turnitinrubricimporter\form;

use local_turnitinrubricimporter\local\parser;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Native Moodle upload form with server-side parser validation.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /** @var array|null Cached parser result for this form submission. */
    private ?array $parsed = null;

    /**
     * Define accessible native form controls.
     */
    public function definition(): void {
        $form = $this->_form;
        $form->addElement('static', 'instructions', '', get_string('instructions', 'local_turnitinrubricimporter'));
        $form->addElement('filepicker', 'rubricfile', get_string('chooserubricfile', 'local_turnitinrubricimporter'), null, [
            'accepted_types' => ['.rbc', '.json', '.csv'],
            'maxbytes' => parser::MAX_BYTES,
            'subdirs' => 0,
        ]);
        $form->addRule('rubricfile', null, 'required');
        $sampleurl = new \moodle_url('/local/turnitinrubricimporter/samplecsv.php', ['cmid' => $this->_customdata['cmid']]);
        $form->addElement(
            'static',
            'sample',
            get_string('csvformatheading', 'local_turnitinrubricimporter'),
            \html_writer::link($sampleurl, get_string('downloadtemplatecsvlink', 'local_turnitinrubricimporter'))
        );
        $form->addElement('static', 'scoringnotice', '', get_string('conversionnotice', 'local_turnitinrubricimporter'));
        $form->addElement(
            'advcheckbox',
            'acknowledgescoring',
            '',
            get_string('acknowledgescoring', 'local_turnitinrubricimporter')
        );
        foreach (['cmid', 'areaid', 'contextid'] as $name) {
            $form->addElement('hidden', $name, $this->_customdata[$name]);
            $form->setType($name, PARAM_INT);
        }
        $this->add_action_buttons(true, get_string('submitfile', 'local_turnitinrubricimporter'));
    }

    /**
     * Validate file ownership, bounded size, schema and conversion acknowledgement.
     *
     * @param array $data Submitted form values
     * @param array $files Submitted files
     * @return array Field errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            $file = $this->get_uploaded_file((int)($data['rubricfile'] ?? 0));
            $this->parsed = parser::parse($file->get_content(), $file->get_filename());
            if ($this->parsed['notices'] && empty($data['acknowledgescoring'])) {
                $errors['acknowledgescoring'] = get_string('acknowledgescoringrequired', 'local_turnitinrubricimporter');
            }
        } catch (\moodle_exception $e) {
            $errors['rubricfile'] = s($e->getMessage());
        }
        return $errors;
    }

    /**
     * Read only the current user's one submitted draft file.
     *
     * @param int $draftid Draft area identifier
     * @return \stored_file Owned upload
     */
    private function get_uploaded_file(int $draftid): \stored_file {
        global $USER;

        if ($draftid <= 0) {
            throw new \moodle_exception('filerequired', 'local_turnitinrubricimporter');
        }
        $context = \context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files($context->id, 'user', 'draft', $draftid, 'id', false);
        if (count($files) !== 1) {
            throw new \moodle_exception('filerequired', 'local_turnitinrubricimporter');
        }
        $file = reset($files);
        if ($file->get_filesize() > parser::MAX_BYTES) {
            throw new \moodle_exception('filetoolarge', 'local_turnitinrubricimporter');
        }
        return $file;
    }

    /**
     * Return the already validated model without reading the file again.
     *
     * @return array Normalised rubric
     */
    public function get_parsed_rubric(): array {
        if ($this->parsed === null) {
            throw new \coding_exception('Rubric form must be validated before importing');
        }
        return $this->parsed;
    }

    /**
     * Remove the successful import's source file from its private draft area.
     */
    public function delete_uploaded_file(): void {
        $data = $this->get_data();
        if ($data) {
            $this->get_uploaded_file((int)$data->rubricfile)->delete();
        }
    }
}
