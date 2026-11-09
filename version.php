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

/**
 * Release metadata for the Moodle 5.2 candidate.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_turnitinrubricimporter';
$plugin->version = 2026110902;
$plugin->requires = 2026042000;
$plugin->supported = [502, 502];
$plugin->maturity = MATURITY_BETA;
$plugin->release = '1.4.0-beta.3';
$plugin->dependencies = ['mod_assign' => ANY_VERSION, 'gradingform_rubric' => ANY_VERSION];
