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

namespace local_turnitinrubricimporter\local;

/**
 * Persist a new rubric atomically through Moodle's grading controller.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class importer {
    /**
     * Import once; never replace a definition or change an already graded assignment.
     *
     * @param int $cmid Assignment course module
     * @param array $parsed Normalised parser result
     * @param string $token Moodle session key
     * @param bool $acknowledgescoring Acknowledgement of generated scores
     * @param int $areaid Optional legacy target grading area
     * @param int $contextid Optional legacy expected context
     * @return int New definition ID
     */
    public static function import(
        int $cmid,
        array $parsed,
        string $token,
        bool $acknowledgescoring = false,
        int $areaid = 0,
        int $contextid = 0
    ): int {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/grading/lib.php');
        [$context, $course, $cm] = target::resolve($cmid, $areaid, $contextid);
        require_login($course, true, $cm);
        target::require_capabilities($context);
        if (!confirm_sesskey($token)) {
            throw new \moodle_exception('invalidsesskey');
        }
        $parsed = parser::validate($parsed);
        if ($parsed['notices'] && !$acknowledgescoring) {
            throw new \moodle_exception('acknowledgescoringrequired', 'local_turnitinrubricimporter');
        }
        $factory = \core\lock\lock_config::get_lock_factory('local_turnitinrubricimporter');
        $lock = $factory->get_lock('assignment:' . $cm->id, 5);
        if (!$lock) {
            throw new \moodle_exception('importbusy', 'local_turnitinrubricimporter');
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            try {
                // Re-read after acquiring the lock, including if the area was absent on GET.
                [$context, , $cm, $area] = target::resolve($cm->id, $areaid, $contextid);
                target::require_capabilities($context);
                if ($area && $DB->record_exists('grading_definitions', ['areaid' => $area->id])) {
                    throw new \moodle_exception('errorrubricexists', 'local_turnitinrubricimporter');
                }
                $assignment = $DB->get_record('assign', ['id' => $cm->instance], 'id,grade', MUST_EXIST);
                if ($assignment->grade <= 0) {
                    throw new \moodle_exception('numericgraderequired', 'local_turnitinrubricimporter');
                }
                if (
                    $DB->record_exists_select(
                        'assign_grades',
                        'assignment = :assignment AND grade >= :minimum',
                        ['assignment' => $assignment->id, 'minimum' => 0]
                    )
                ) {
                    throw new \moodle_exception('assignmentalreadygraded', 'local_turnitinrubricimporter');
                }
                $manager = get_grading_manager($context, 'mod_assign', 'submissions');
                $manager->set_active_method('rubric');
                $controller = $manager->get_controller('rubric');
                $definition = new \stdClass();
                $definition->name = $parsed['name'];
                $definition->status = \gradingform_controller::DEFINITION_STATUS_READY;
                $definition->description_editor = [
                    'text' => $parsed['description'], 'format' => FORMAT_PLAIN, 'itemid' => 0,
                ];
                $options = \gradingform_rubric_controller::get_default_options();
                $options['sortlevelsasc'] = 0;
                $definition->rubric = ['options' => $options, 'criteria' => []];
                foreach (array_values($parsed['criteria']) as $index => $criterion) {
                    $entry = [
                        'description' => $criterion['description'],
                        'descriptionformat' => FORMAT_PLAIN,
                        'sortorder' => $index,
                        'levels' => [],
                    ];
                    foreach (array_values($criterion['levels']) as $levelindex => $level) {
                        $entry['levels']['NEWID' . ($levelindex + 1)] = [
                            'definition' => $level['definition'],
                            'definitionformat' => FORMAT_PLAIN,
                            'score' => $level['score'],
                        ];
                    }
                    $definition->rubric['criteria']['NEWID' . ($index + 1)] = $entry;
                }
                $controller->update_definition($definition);
                $id = (int)$controller->get_definition()->id;
                \local_turnitinrubricimporter\event\rubric_imported::create([
                    'context' => $context, 'objectid' => $id,
                ])->trigger();
                $transaction->allow_commit();
                return $id;
            } catch (\Throwable $e) {
                $transaction->rollback($e);
            }
        } finally {
            $lock->release();
        }
    }
}
