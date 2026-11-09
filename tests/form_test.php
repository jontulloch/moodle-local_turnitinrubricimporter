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

namespace local_turnitinrubricimporter;

use local_turnitinrubricimporter\form\import_form;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Private draft file ownership and form validation tests.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(import_form::class)]
final class form_test extends \advanced_testcase {
    /**
     * Build an upload form for a synthetic assignment and current teacher.
     *
     * @return array Form, teacher
     */
    private function form(): array {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $PAGE->set_context(\context_module::instance($activity->cmid));
        $form = new import_form(null, ['cmid' => $activity->cmid, 'areaid' => 0, 'contextid' => 0]);
        return [$form, $teacher];
    }

    /**
     * Create a draft upload belonging to a specific user.
     *
     * @param int $userid File owner
     * @param int $draftid Draft identifier
     * @param string $content Source content
     * @param string $filename Source filename
     * @return \stored_file Test upload
     */
    private function upload(int $userid, int $draftid, string $content, string $filename = 'rubric.csv'): \stored_file {
        return get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($userid)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => $filename, 'userid' => $userid,
        ], $content);
    }

    /**
     * An authorised teacher can validate their own one-file draft.
     */
    public function test_own_file_is_valid(): void {
        [$form, $teacher] = $this->form();
        $this->upload($teacher->id, 123, file_get_contents(__DIR__ . '/fixtures/sample.csv'));
        $this->assertSame([], $form->validation(['rubricfile' => 123], []));
        $this->assertCount(1, $form->get_parsed_rubric()['criteria']);
    }

    /**
     * A supplied draft ID does not grant access to another user's upload.
     */
    public function test_foreign_file_is_rejected(): void {
        [$form] = $this->form();
        $other = $this->getDataGenerator()->create_user();
        $this->upload($other->id, 123, file_get_contents(__DIR__ . '/fixtures/sample.csv'));
        $errors = $form->validation(['rubricfile' => 123], []);
        $this->assertArrayHasKey('rubricfile', $errors);
    }

    /**
     * Validate the real stored file size before loading its content.
     */
    public function test_oversized_stored_upload_is_rejected(): void {
        [$form, $teacher] = $this->form();
        $this->upload($teacher->id, 123, str_repeat('a', 2097153));
        $errors = $form->validation(['rubricfile' => 123], []);
        $this->assertArrayHasKey('rubricfile', $errors);
    }

    /**
     * Show a conversion acknowledgement error against its labelled control.
     */
    public function test_generated_scores_require_acknowledgement(): void {
        [$form, $teacher] = $this->form();
        $this->upload($teacher->id, 123, file_get_contents(__DIR__ . '/fixtures/qualitative.rbc'), 'rubric.rbc');
        $errors = $form->validation(['rubricfile' => 123], []);
        $this->assertArrayHasKey('acknowledgescoring', $errors);
        $this->assertSame([], $form->validation(['rubricfile' => 123, 'acknowledgescoring' => 1], []));
    }
}
