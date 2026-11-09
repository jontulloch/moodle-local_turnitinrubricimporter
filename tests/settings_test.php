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

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Verify that the installed plugin is discoverable in site administration.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class settings_test extends \advanced_testcase {
    /**
     * The local plugin should register an information page without configuration values.
     */
    public function test_admin_information_page_is_registered(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $root = admin_get_root(true, true);
        $page = $root->locate('local_turnitinrubricimporter');
        $this->assertInstanceOf(\admin_settingpage::class, $page);
        $this->assertSame(get_string('pluginname', 'local_turnitinrubricimporter'), $page->visiblename);
    }
}
