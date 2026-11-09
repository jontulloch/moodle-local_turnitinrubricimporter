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

use local_turnitinrubricimporter\privacy\provider;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Privacy declaration and core compliance tests.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class privacy_test extends \basic_testcase {
    /**
     * The no-separate-store declaration must describe delegated processing.
     */
    public function test_core_owned_processing_is_explained(): void {
        $reason = get_string(provider::get_reason(), 'local_turnitinrubricimporter');
        $this->assertStringContainsString('core grading', $reason);
        $this->assertStringContainsString('draft', $reason);
        $this->assertTrue((new \core_privacy\manager())->component_is_compliant('local_turnitinrubricimporter'));
    }
}
