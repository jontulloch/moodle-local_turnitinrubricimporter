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

use local_turnitinrubricimporter\local\importer;
use local_turnitinrubricimporter\local\parser;
use local_turnitinrubricimporter\local\target;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Authorisation, core persistence, rollback and duplicate-import regressions.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(importer::class)]
#[CoversClass(target::class)]
final class importer_test extends \advanced_testcase {
    /**
     * Create an ungraded numeric Assignment and enrolled editing teacher.
     *
     * @return array Module, context, teacher, editing-teacher role ID
     */
    private function assignment(): array {
        global $DB;
        $this->resetAfterTest();
        $this->redirectMessages();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'grade' => 100]);
        $teacher = $this->getDataGenerator()->create_user();
        $role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $role->id);
        $this->setUser($teacher);
        return [$activity, \context_module::instance($activity->cmid), $teacher, $role->id];
    }

    /**
     * Parse a synthetic fixture.
     *
     * @param string $name Fixture name
     * @return array Parser model
     */
    private function parsed(string $name = 'sample.csv'): array {
        return parser::parse(file_get_contents(__DIR__ . '/fixtures/' . $name), $name);
    }

    /**
     * Create one ready rubric with valid author metadata and plain text fields.
     */
    public function test_successful_import_uses_core_controller(): void {
        global $DB;
        [$activity, $context, $teacher] = $this->assignment();
        $sink = $this->redirectEvents();
        $id = importer::import($activity->cmid, $this->parsed(), sesskey());
        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof \local_turnitinrubricimporter\event\rubric_imported
        ));
        $this->assertCount(1, $events);
        $this->assertEquals($id, $events[0]->objectid);
        $sink->close();
        $record = $DB->get_record('grading_definitions', ['id' => $id], '*', MUST_EXIST);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('rubric');
        $this->assertSame('rubric', $manager->get_active_method());
        $this->assertEquals(\gradingform_controller::DEFINITION_STATUS_READY, $record->status);
        $this->assertEquals($teacher->id, $record->usercreated);
        $this->assertCount(1, $controller->get_definition()->rubric_criteria);
        $this->assertSame(['minscore' => 0.0, 'maxscore' => 10.0], $controller->get_min_max_score());
        $this->assertEquals(FORMAT_PLAIN, $DB->get_field(
            'gradingform_rubric_criteria',
            'descriptionformat',
            ['definitionid' => $id]
        ));
        $this->assertEquals(0, $DB->count_records('assign_grades', ['assignment' => $activity->id]));
    }

    /**
     * Invalid session keys cannot create a grading area.
     */
    public function test_invalid_sesskey_has_no_side_effects(): void {
        global $DB;
        [$activity, $context] = $this->assignment();
        try {
            importer::import($activity->cmid, $this->parsed(), 'invalid');
            $this->fail('Session key was not enforced');
        } catch (\moodle_exception $e) {
            $this->assertSame('invalidsesskey', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('grading_areas', ['contextid' => $context->id]));
    }

    /**
     * A capability in course A must not permit writes in course B.
     */
    public function test_foreign_area_and_context_are_rejected(): void {
        global $DB;
        [$first, $context] = $this->assignment();
        $othercourse = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_module('assign', ['course' => $othercourse->id, 'grade' => 100]);
        $othercontext = \context_module::instance($other->cmid);
        $areaid = $DB->insert_record('grading_areas', (object)[
            'contextid' => $othercontext->id, 'component' => 'mod_assign', 'areaname' => 'submissions',
        ]);
        try {
            importer::import($first->cmid, $this->parsed(), sesskey(), false, $areaid, $context->id);
            $this->fail('Mismatched target was accepted');
        } catch (\moodle_exception $e) {
            $this->assertSame('invalidtarget', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('grading_definitions', ['areaid' => $areaid]));
    }

    /**
     * A teacher with valid target IDs still needs permissions in that target course.
     */
    public function test_teacher_cannot_import_into_another_course(): void {
        [$first] = $this->assignment();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $this->expectException(\moodle_exception::class);
        importer::import($other->cmid, $this->parsed(), sesskey());
    }

    /**
     * Both the custom capability and the core capability must be enforced.
     */
    public function test_custom_capability_can_disable_import(): void {
        [$activity, $context, $teacher, $roleid] = $this->assignment();
        assign_capability('local/turnitinrubricimporter:import', CAP_PROHIBIT, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        importer::import($activity->cmid, $this->parsed(), sesskey());
    }

    /**
     * The custom capability cannot override a denied core management capability.
     */
    public function test_core_capability_is_required(): void {
        [$activity, $context, $teacher, $roleid] = $this->assignment();
        assign_capability('moodle/grade:managegradingforms', CAP_PROHIBIT, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        importer::import($activity->cmid, $this->parsed(), sesskey());
    }

    /**
     * Students may not create rubric definitions.
     */
    public function test_student_is_denied(): void {
        [$activity] = $this->assignment();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $activity->course, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        importer::import($activity->cmid, $this->parsed(), sesskey());
    }

    /**
     * Duplicate requests cannot replace the first definition.
     */
    public function test_second_import_preserves_existing_rubric(): void {
        global $DB;
        [$activity] = $this->assignment();
        $id = importer::import($activity->cmid, $this->parsed(), sesskey());
        $before = $DB->get_record('grading_definitions', ['id' => $id]);
        try {
            importer::import($activity->cmid, $this->parsed(), sesskey());
            $this->fail('Existing rubric was overwritten');
        } catch (\moodle_exception $e) {
            $this->assertSame('errorrubricexists', $e->errorcode);
        }
        $this->assertEquals($before, $DB->get_record('grading_definitions', ['id' => $id]));
        $this->assertEquals(1, $DB->count_records('grading_definitions', ['areaid' => $before->areaid]));
    }

    /**
     * Generated scoring requires an explicit acknowledgement.
     */
    public function test_conversion_acknowledgement_is_required(): void {
        global $DB;
        [$activity, $context] = $this->assignment();
        try {
            importer::import($activity->cmid, $this->parsed('qualitative.rbc'), sesskey());
            $this->fail('Conversion was not acknowledged');
        } catch (\moodle_exception $e) {
            $this->assertSame('acknowledgescoringrequired', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('grading_areas', ['contextid' => $context->id]));
        $this->assertGreaterThan(0, importer::import($activity->cmid, $this->parsed('qualitative.rbc'), sesskey(), true));
    }

    /**
     * Read-only target resolution does not create a grading area.
     */
    public function test_get_target_resolution_is_read_only(): void {
        global $DB;
        [$activity, $context] = $this->assignment();
        target::resolve($activity->cmid);
        target::management_url($context);
        $this->assertFalse($DB->record_exists('grading_areas', ['contextid' => $context->id]));
    }

    /**
     * An existing grading guide must not be deactivated by an attempted import.
     */
    public function test_other_definition_is_preserved(): void {
        global $DB, $USER, $CFG;
        [$activity, $context] = $this->assignment();
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $manager->set_active_method('guide');
        [, , , $area] = target::resolve($activity->cmid);
        $DB->insert_record('grading_definitions', (object)[
            'areaid' => $area->id, 'method' => 'guide', 'name' => 'Existing guide',
            'status' => 20, 'timecreated' => time(), 'timemodified' => time(),
            'usercreated' => $USER->id, 'usermodified' => $USER->id,
        ]);
        try {
            importer::import($activity->cmid, $this->parsed(), sesskey());
            $this->fail('Existing guide was replaced');
        } catch (\moodle_exception $e) {
            $this->assertSame('errorrubricexists', $e->errorcode);
        }
        $this->assertSame('guide', $DB->get_field('grading_areas', 'activemethod', ['id' => $area->id]));
    }

    /**
     * A recorded student grade prevents switching the assessment method.
     */
    public function test_recorded_grades_are_preserved(): void {
        global $DB;
        [$activity, $context, $teacher] = $this->assignment();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $activity->course, 'student');
        $id = $DB->insert_record('assign_grades', (object)[
            'assignment' => $activity->id, 'userid' => $student->id, 'timecreated' => time(),
            'timemodified' => time(), 'grader' => $teacher->id, 'grade' => 0,
        ]);
        $before = $DB->get_record('assign_grades', ['id' => $id]);
        try {
            importer::import($activity->cmid, $this->parsed(), sesskey());
            $this->fail('Graded assignment accepted a new method');
        } catch (\moodle_exception $e) {
            $this->assertSame('assignmentalreadygraded', $e->errorcode);
        }
        $this->assertEquals($before, $DB->get_record('assign_grades', ['id' => $id]));
        $this->assertFalse($DB->record_exists('grading_areas', ['contextid' => $context->id]));
    }

    /**
     * Roll back the area, method, definition and criteria on a mid-write failure.
     */
    public function test_mid_write_failure_rolls_back_everything(): void {
        global $DB;
        // Observe the importer's real rollback rather than PHPUnit's enclosing transaction.
        $this->preventResetByRollback();
        [$activity, $context] = $this->assignment();
        $realdb = $DB;
        $DB = new class ($realdb) {
            /** @var int Number of level writes attempted. */
            private int $levels = 0;
            /** @var \moodle_database Real connection. */
            private \moodle_database $realdb;
            /**
             * Keep the real connection for all unmodified operations.
             * @param \moodle_database $realdb Actual database connection
             */
            public function __construct(\moodle_database $realdb) {
                $this->realdb = $realdb;
            }
            /**
             * Inject a failure after some rows have actually been written.
             * @param string $table Target table
             * @param mixed $record Target record
             * @param bool $returnid Return new ID
             * @param bool $bulk Bulk flag
             * @return mixed Database result
             */
            public function insert_record($table, $record, $returnid = true, $bulk = false): mixed {
                if ($table === 'gradingform_rubric_levels' && ++$this->levels === 2) {
                    throw new \moodle_exception('importerror', 'local_turnitinrubricimporter');
                }
                return $this->realdb->insert_record($table, $record, $returnid, $bulk);
            }
            /**
             * Forward every other operation unchanged.
             * @param string $name Method name
             * @param array $args Method arguments
             * @return mixed Database result
             */
            public function __call(string $name, array $args): mixed {
                return $this->realdb->$name(...$args);
            }
        };
        try {
            importer::import($activity->cmid, $this->parsed(), sesskey());
            $this->fail('Injected database failure did not occur');
        } catch (\moodle_exception $e) {
            $this->assertSame('importerror', $e->errorcode);
        } finally {
            $DB = $realdb;
        }
        $this->assertFalse($DB->record_exists('grading_areas', ['contextid' => $context->id]));
        $this->assertEquals(0, $DB->count_records('gradingform_rubric_criteria'));
        $this->assertEquals(0, $DB->count_records('gradingform_rubric_levels'));
        $this->assertTrue($DB->record_exists('assign', ['id' => $activity->id]));
    }
}
