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
     * Render native actions and place the management card with a scoped AMD module.
     *
     * @param before_footer_html_generation $hook Output hook
     */
    public static function grading_import_link(before_footer_html_generation $hook): void {
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
        // Core has already resolved and authorised the grading page's context.
        // Use it rather than depending on the shape of the incoming query string.
        $context = $PAGE->context;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        try {
            [$context, , $cm, $area] = target::resolve((int)$context->instanceid);
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
        if ($path === $basepath . '/grade/grading/manage.php') {
            $renderer = $PAGE->get_renderer('core');
            // Match core grading cards, explicitly sizing this non-b/ icon too.
            $icon = $renderer->pix_icon('i/import', '', 'moodle', ['class' => 'iconsize-big']);
            $hook->add_html(\html_writer::div(
                \html_writer::link(
                    $url,
                    $icon . \html_writer::div(get_string('importfromfile', 'local_turnitinrubricimporter'), 'action-text'),
                    ['class' => 'action btn btn-lg']
                ),
                'actions local-turnitinrubricimporter',
                ['id' => 'local-turnitinrubricimporter-actions']
            ));
            $PAGE->requires->js_call_amd('local_turnitinrubricimporter/grading_actions', 'init');
            return;
        }
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
