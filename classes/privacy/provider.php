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

namespace local_turnitinrubricimporter\privacy;

/**
 * Explain that the importer has no plugin-owned personal data store.
 *
 * Core grading owns the rubric and its author metadata, core user/file APIs own
 * draft uploads, and the configured Moodle logstore owns import events. Their
 * providers service retention, export and deletion. Do not independently erase
 * shared assessment records from a local plugin privacy request.
 *
 * @package local_turnitinrubricimporter
 * @category privacy
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Return the reason that this plugin has no separate data to export or erase.
     *
     * @return string Language string identifier
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
