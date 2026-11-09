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

use core\hook\output\before_footer_html_generation;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Output hook visibility and page-load cost regressions.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Unrelated pages must not query the database or load any markup or script.
     */
    public function test_unrelated_page_has_no_database_work(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/course/view.php', ['id' => 1]);
        $hook = new before_footer_html_generation(new \core_renderer($PAGE, RENDERER_TARGET_GENERAL));
        $reads = $DB->perf_get_reads();
        $writes = $DB->perf_get_writes();
        hook_callbacks::grading_import_link($hook);
        $this->assertSame('', $hook->get_output());
        $this->assertSame($reads, $DB->perf_get_reads());
        $this->assertSame($writes, $DB->perf_get_writes());
    }

    /**
     * A permitted editor sees the import action, without an existing rubric.
     */
    public function test_grading_page_has_a_native_link(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $PAGE->set_context($context);
        $params = ['contextid' => $context->id, 'component' => 'mod_assign', 'area' => 'submissions'];
        $PAGE->set_url('/grade/grading/manage.php', $params);
        $_GET = $params;
        $hook = new before_footer_html_generation(new \core_renderer($PAGE, RENDERER_TARGET_GENERAL));
        hook_callbacks::grading_import_link($hook);
        $this->assertStringContainsString('/local/turnitinrubricimporter/import.php', $hook->get_output());
        $this->assertStringNotContainsString('<script', $hook->get_output());
    }

    /**
     * Real page rendering must place the registered action inside the main content.
     */
    public function test_registered_hook_renders_link_inside_main_content(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_id('assign', $activity->cmid, $course->id, false, MUST_EXIST);
        $PAGE->set_cm($cm, $course);
        $PAGE->set_url(
            '/grade/grading/manage.php',
            [
                'contextid' => $PAGE->context->id,
                'component' => 'mod_assign',
                'area' => 'submissions',
            ]
        );
        $PAGE->set_pagelayout('incourse');
        $PAGE->force_theme('boost');
        $PAGE->set_title('Rubric import integration');
        $PAGE->set_heading($course->fullname);
        // No GET parameters: the action must use the context resolved by core.
        $_GET = [];
        // PHPUnit runs in CLI; explicitly exercise the actual web theme renderer.
        $output = new \theme_boost\output\core_renderer($PAGE, RENDERER_TARGET_GENERAL);
        $bufferlevel = ob_get_level();
        try {
            $html = $output->header() . $output->footer();
        } finally {
            while (ob_get_level() > $bufferlevel) {
                ob_end_clean();
            }
        }
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $this->assertTrue($document->loadHTML($html));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//*[@role="main"]//a[contains(@href, "/local/turnitinrubricimporter/import.php")]');
        $this->assertCount(1, $links);
        $this->assertStringContainsString('cmid=' . $activity->cmid, $links->item(0)->getAttribute('href'));
    }
}
