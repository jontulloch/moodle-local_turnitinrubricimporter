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

use core\hook\output\after_standard_main_region_html_generation;
use local_turnitinrubricimporter\local\target;

/**
 * Add a native import link only to the relevant grading pages.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Render without JavaScript, DOM matching, page-wide CSS or core overrides.
     *
     * @param after_standard_main_region_html_generation $hook Output hook
     */
    public static function grading_import_link(after_standard_main_region_html_generation $hook): void {
        global $CFG, $DB, $PAGE;

        $path = $PAGE->url->get_path();
        $basepath = rtrim((string)parse_url($CFG->wwwroot, PHP_URL_PATH), '/');
        $paths = [$basepath . '/grade/grading/manage.php', $basepath . '/grade/grading/form/rubric/edit.php'];
        // Return before configuration or database work on every unrelated page.
        if (
            !in_array($path, $paths, true) || during_initial_install() || !empty($CFG->upgraderunning) ||
                !isloggedin() || isguestuser()
        ) {
            return;
        }
        $areaid = optional_param('areaid', 0, PARAM_INT);
        $contextid = optional_param('contextid', 0, PARAM_INT);
        $cmid = 0;
        if (!$areaid && $contextid) {
            if (
                optional_param('component', '', PARAM_COMPONENT) !== 'mod_assign' ||
                    optional_param('area', '', PARAM_AREA) !== 'submissions'
            ) {
                return;
            }
            $context = \context::instance_by_id($contextid, IGNORE_MISSING);
            if (!$context || $context->contextlevel !== CONTEXT_MODULE) {
                return;
            }
            $cmid = (int)$context->instanceid;
        }
        if (!$cmid && !$areaid) {
            return;
        }
        try {
            [$context, , $cm, $area] = target::resolve($cmid, $areaid, $contextid);
        } catch (\moodle_exception $e) {
            return;
        }
        if (
            !has_capability('moodle/grade:managegradingforms', $context) ||
                !has_capability('local/turnitinrubricimporter:import', $context) ||
                ($area && $DB->record_exists('grading_definitions', ['areaid' => $area->id]))
        ) {
            return;
        }
        $url = new \moodle_url('/local/turnitinrubricimporter/import.php', ['cmid' => $cm->id]);
        $hook->add_html(\html_writer::div(
            \html_writer::link(
                $url,
                get_string('importfromfile', 'local_turnitinrubricimporter'),
                ['class' => 'btn btn-secondary']
            ),
            'local-turnitinrubricimporter mt-3 mb-3'
        ));
    }
}
