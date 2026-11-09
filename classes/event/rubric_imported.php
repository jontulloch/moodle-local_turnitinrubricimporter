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

namespace local_turnitinrubricimporter\event;

/**
 * Successful rubric import, recorded through Moodle's configured logstore.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rubric_imported extends \core\event\base {
    /**
     * Set standard event metadata, without filenames or uploaded rubric text.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'grading_definitions';
    }

    /**
     * Get a translated event name.
     *
     * @return string Event name
     */
    public static function get_name(): string {
        return get_string('eventrubricimported', 'local_turnitinrubricimporter');
    }

    /**
     * Describe the event without student details or source file contents.
     *
     * @return string Description
     */
    public function get_description(): string {
        return "The user with id '$this->userid' imported rubric definition '$this->objectid' " .
            "in course module '$this->contextinstanceid'.";
    }

    /**
     * Link to the relevant grading management page.
     *
     * @return \moodle_url Target URL
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/grade/grading/manage.php', [
            'contextid' => $this->contextid, 'component' => 'mod_assign', 'area' => 'submissions',
        ]);
    }
}
