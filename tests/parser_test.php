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

use local_turnitinrubricimporter\local\parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * File schema, score integrity and resource-boundary regressions.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(parser::class)]
final class parser_test extends \basic_testcase {
    /**
     * Read a synthetic export; these fixtures contain no institutional data.
     *
     * @param string $name Fixture name
     * @return string Source content
     */
    private static function fixture(string $name): string {
        return file_get_contents(__DIR__ . '/fixtures/' . $name);
    }

    /**
     * Supported RBC conversion cases.
     *
     * @return array Test cases
     */
    public static function rbc_provider(): array {
        return [
            'standard' => ['standard', [100.0, 0.0], []],
            'custom' => ['custom', [10.0, 0.0], []],
            'qualitative' => ['qualitative', [1.0, 0.0], ['noticequalitative']],
            'gradingform' => ['gradingform', [0.0, 100.0], ['noticegradingform']],
        ];
    }

    /**
     * Convert each supported source type with expected scores and notices.
     *
     * @param string $type Fixture name
     * @param array $scores Expected numeric scores
     * @param array $notices Expected conversion acknowledgements
     */
    #[DataProvider('rbc_provider')]
    public function test_supported_rbc(string $type, array $scores, array $notices): void {
        $parsed = parser::parse(self::fixture($type . '.rbc'), $type . '.rbc');
        $this->assertCount(1, $parsed['criteria']);
        $this->assertSame($scores, array_column($parsed['criteria'][0]['levels'], 'score'));
        $this->assertSame($notices, $parsed['notices']);
    }

    /**
     * Parse BOM, reordered headers, quotes and a multiline description.
     */
    public function test_csv_quoting_and_numeric_criterion_names(): void {
        $csv = "\xEF\xBB\xBFscore,level_description,level,criterion\r\n"
            . "10,\"A comma, and a\nnew line\",High,123\r\n0,\"Quoted \"\"word\"\"\",Low,123\r\n";
        $parsed = parser::parse($csv, 'RUBRIC.CSV');
        $this->assertSame('123', $parsed['criteria'][0]['description']);
        $this->assertSame("High\nA comma, and a\nnew line", $parsed['criteria'][0]['levels'][0]['definition']);
        $this->assertSame("Low\nQuoted \"word\"", $parsed['criteria'][0]['levels'][1]['definition']);
    }

    /**
     * Do not substitute a standard weighting for a custom zero.
     */
    public function test_custom_zero_is_preserved_with_nonzero_scale_weight(): void {
        $data = json_decode(self::fixture('custom.rbc'), true);
        $data['RubricScale'][1]['value'] = 50;
        $parsed = parser::parse(json_encode($data), 'custom.rbc');
        $this->assertSame(0.0, $parsed['criteria'][0]['levels'][1]['score']);
    }

    /**
     * Preserve separate RBC records with the same visible description.
     */
    public function test_equal_criterion_text_does_not_overwrite_records(): void {
        $data = json_decode(self::fixture('custom.rbc'), true);
        $second = $data['RubricCriterion'][0];
        $second['id'] = 'c2';
        $second['position'] = 2;
        $data['RubricCriterion'][] = $second;
        foreach ($data['RubricCriterionScale'] as $cell) {
            $cell['id'] .= 'b';
            $cell['criterion'] = 'c2';
            $data['RubricCriterionScale'][] = $cell;
        }
        $parsed = parser::parse(json_encode($data), 'custom.rbc');
        $this->assertCount(2, $parsed['criteria']);
        $this->assertSame($parsed['criteria'][0]['description'], $parsed['criteria'][1]['description']);
    }

    /**
     * Imported tags and encoded tags are never trusted HTML.
     */
    public function test_html_is_plain_text(): void {
        $csv = "criterion,level,level_description,score\n"
            . '"<b>Clarity</b>",High,"&lt;img src=x onerror=alert(1)&gt;Text<br>Next",10' . "\n"
            . '"<b>Clarity</b>",Low,"<script>alert(2)</script>",0' . "\n";
        $parsed = parser::parse($csv, 'safe.csv');
        $this->assertSame('Clarity', $parsed['criteria'][0]['description']);
        $this->assertSame("High\nText\nNext", $parsed['criteria'][0]['levels'][0]['definition']);
        $this->assertStringNotContainsString('<', json_encode($parsed));
    }

    /**
     * Ignore an unused empty placeholder column, without dropping scored data.
     */
    public function test_unused_placeholder_scale_is_ignored(): void {
        $data = json_decode(self::fixture('standard.rbc'), true);
        $data['RubricScale'][] = ['id' => 'empty', 'name' => 'Empty Scale', 'value' => 0, 'position' => 3];
        $parsed = parser::parse(json_encode($data), 'rubric.rbc');
        $this->assertCount(2, $parsed['criteria'][0]['levels']);
        $data['RubricScale'][2]['value'] = 20;
        $this->expectException(\moodle_exception::class);
        parser::parse(json_encode($data), 'rubric.rbc');
    }

    /**
     * Invalid input cases, including numeric and workload boundaries.
     *
     * @return array Invalid file contents and expected exception keys
     */
    public static function invalid_provider(): array {
        $prefix = "criterion,level,level_description,score\nC,High,Good,10\nC,Low,Bad,";
        $cases = [
            'empty' => ['', 'x.csv', 'invalidencoding'],
            'null byte' => ["\0", 'x.rbc', 'invalidencoding'],
            'invalid utf8' => ["\xFF", 'x.csv', 'invalidencoding'],
            'too many bytes' => [str_repeat('x', parser::MAX_BYTES + 1), 'x.csv', 'filetoolarge'],
            'extension' => [$prefix . '0', 'x.php', 'unsupportedfile'],
            'malformed json' => ['{', 'x.rbc', 'rbcinvalidjson'],
            'deep json' => [str_repeat('[', 40) . '0' . str_repeat(']', 40), 'x.rbc', 'rbcinvalidjson'],
            'scalar json' => ['true', 'x.rbc', 'invalidstructure'],
            'missing columns' => ['criterion,level,score', 'x.csv', 'csvmissingcolumns'],
            'duplicate headers' => ['criterion,level,score,score', 'x.csv', 'csvmissingcolumns'],
            'duplicate scores' => [$prefix . '10', 'x.csv', 'errorrepeatedscores'],
            'negative score' => [$prefix . '-1', 'x.csv', 'invalidscore'],
            'overflow' => [$prefix . '1e309', 'x.csv', 'invalidscore'],
            'database overflow' => [$prefix . '100000', 'x.csv', 'invalidscore'],
            'excess decimals' => [$prefix . '0.000001', 'x.csv', 'invalidscore'],
            'tiny excess decimals' => [$prefix . '0.0000000001', 'x.csv', 'invalidscore'],
            'nan' => [$prefix . 'NaN', 'x.csv', 'invalidscore'],
            'extra column' => [$prefix . '0,extra', 'x.csv', 'csvbadrow'],
            'one level' => ["criterion,level,level_description,score\nC,High,Good,10", 'x.csv', 'rubriclimits'],
            'repeated level' => ["criterion,level,level_description,score\nC,A,Good,10\nC,A,Bad,0", 'x.csv', 'duplicatelevel'],
            'long text' => ['criterion,level,level_description,score' . "\n" . str_repeat('x', 10001) . ',A,,10',
                'x.csv', 'invalidstructure'],
        ];
        foreach (['method', 'missing scales', 'cell reference', 'duplicate cell', 'duplicate id', 'scalar record'] as $change) {
            $data = json_decode(file_get_contents(__DIR__ . '/fixtures/standard.rbc'), true);
            switch ($change) {
                case 'method':
                    $data['Rubric'][0]['scoring_method'] = 99;
                    break;
                case 'missing scales':
                    unset($data['RubricScale']);
                    break;
                case 'cell reference':
                    $data['RubricCriterionScale'][0]['criterion'] = 'unknown';
                    break;
                case 'duplicate cell':
                    $cell = $data['RubricCriterionScale'][0];
                    $cell['id'] = 'different';
                    $data['RubricCriterionScale'][] = $cell;
                    break;
                case 'duplicate id':
                    $data['RubricScale'][1]['id'] = $data['RubricScale'][0]['id'];
                    break;
                case 'scalar record':
                    $data['RubricCriterion'][0] = 'not an object';
                    break;
            }
            $cases[$change] = [json_encode($data), 'x.rbc', $change === 'method' ? 'rbcunsupported' : 'invalidstructure'];
        }
        $csv = "criterion,level,level_description,score\n";
        for ($i = 0; $i < 101; $i++) {
            $csv .= "C$i,A,,10\nC$i,B,,0\n";
        }
        $cases['criteria bound'] = [$csv, 'x.csv', 'rubriclimits'];
        $csv = "criterion,level,level_description,score\n";
        for ($i = 0; $i < 21; $i++) {
            $csv .= "C,L$i,,$i\n";
        }
        $cases['level bound'] = [$csv, 'x.csv', 'rubriclimits'];
        return $cases;
    }

    /**
     * Reject malformed exports rather than generating a different rubric.
     *
     * @param string $content Source content
     * @param string $filename Source filename
     * @param string $errorcode Expected localised exception key
     */
    #[DataProvider('invalid_provider')]
    public function test_invalid_files(string $content, string $filename, string $errorcode): void {
        try {
            parser::parse($content, $filename);
            $this->fail('Invalid file was accepted');
        } catch (\moodle_exception $e) {
            $this->assertSame($errorcode, $e->errorcode);
        }
    }
}
