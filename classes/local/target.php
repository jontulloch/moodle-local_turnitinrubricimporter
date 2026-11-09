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
 * Bind all request identifiers to one Moodle Assignment.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class target {
    /**
     * Resolve a new cmid URL or validate all identifiers on a legacy URL.
     *
     * This method only reads data; obtaining a grading controller may create a
     * grading area, so that must only happen inside the POST import transaction.
     *
     * @param int $cmid Assignment course module ID, or zero for a legacy link
     * @param int $areaid Optional grading area ID
     * @param int $contextid Optional expected module context
     * @return array Context, course, module, existing grading area or false
     */
    public static function resolve(int $cmid, int $areaid = 0, int $contextid = 0): array {
        global $DB;

        $area = false;
        if ($areaid) {
            $area = $DB->get_record('grading_areas', ['id' => $areaid], '*', MUST_EXIST);
            $areacontext = \context::instance_by_id($area->contextid, MUST_EXIST);
            if (
                $areacontext->contextlevel !== CONTEXT_MODULE || $area->component !== 'mod_assign' ||
                    $area->areaname !== 'submissions' || ($cmid && $cmid !== (int)$areacontext->instanceid)
            ) {
                throw new \moodle_exception('invalidtarget', 'local_turnitinrubricimporter');
            }
            $cmid = (int)$areacontext->instanceid;
        }
        if ($cmid <= 0) {
            throw new \moodle_exception('invalidtarget', 'local_turnitinrubricimporter');
        }
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        if (($contextid && $contextid !== (int)$context->id) || ($area && (int)$area->contextid !== (int)$context->id)) {
            throw new \moodle_exception('invalidtarget', 'local_turnitinrubricimporter');
        }
        $course = get_course($cm->course);
        if (!$area) {
            $area = $DB->get_record('grading_areas', [
                'contextid' => $context->id,
                'component' => 'mod_assign',
                'areaname' => 'submissions',
            ]);
        }
        return [$context, $course, $cm, $area];
    }

    /**
     * Require both capabilities in the actual destination context.
     *
     * @param \context_module $context Target context
     */
    public static function require_capabilities(\context_module $context): void {
        require_capability('moodle/grade:managegradingforms', $context);
        require_capability('local/turnitinrubricimporter:import', $context);
    }

    /**
     * Build a destination URL without trusting a caller-supplied return URL.
     *
     * @param \context_module $context Target context
     * @return \moodle_url Grading management URL
     */
    public static function management_url(\context_module $context): \moodle_url {
        return new \moodle_url('/grade/grading/manage.php', [
            'contextid' => $context->id,
            'component' => 'mod_assign',
            'area' => 'submissions',
        ]);
    }
}
