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
 * Bounded, non-persistent conversion of exported rubric files.
 *
 * @package local_turnitinrubricimporter
 * @copyright 2025 Richard Rangel
 * @copyright 2026 University of East London
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class parser {
    /** @var int Maximum source file size, independently enforced server-side. */
    public const MAX_BYTES = 2097152;
    /** @var int Maximum criteria in one rubric. */
    public const MAX_CRITERIA = 100;
    /** @var int Maximum levels in one criterion. */
    public const MAX_LEVELS = 20;
    /** @var int Maximum cells or CSV data rows. */
    public const MAX_CELLS = 2000;
    /** @var int Maximum bytes in an individual description. */
    public const MAX_TEXT_BYTES = 10000;

    /**
     * Parse a supported file, rejecting ambiguous types and invalid UTF-8.
     *
     * @param string $content Source bytes
     * @param string $filename Original filename, used only as metadata
     * @return array Normalised rubric
     */
    public static function parse(string $content, string $filename): array {
        if (strlen($content) > self::MAX_BYTES) {
            self::fail('filetoolarge');
        }
        if ($content === '' || !mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            self::fail('invalidencoding');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $parsed = match ($extension) {
            'csv' => self::parse_csv($content),
            'rbc', 'json' => self::parse_rbc($content),
            default => self::fail('unsupportedfile'),
        };
        return self::validate($parsed);
    }

    /**
     * Validate the complete normalised model again at the persistence boundary.
     *
     * @param array $parsed Normalised rubric
     * @return array Validated rubric
     */
    public static function validate(array $parsed): array {
        if (
            !isset($parsed['name'], $parsed['description'], $parsed['criteria'], $parsed['notices']) ||
                !is_array($parsed['criteria']) || !is_array($parsed['notices'])
        ) {
            self::fail('invalidstructure');
        }
        $parsed['name'] = self::plain_text($parsed['name']);
        $parsed['description'] = self::plain_text($parsed['description']);
        if ($parsed['name'] === '' || \core_text::strlen($parsed['name']) > 255) {
            self::fail('invalidname');
        }
        if (count($parsed['criteria']) < 1 || count($parsed['criteria']) > self::MAX_CRITERIA) {
            self::fail('rubriclimits');
        }
        $cells = 0;
        foreach ($parsed['criteria'] as &$criterion) {
            if (
                !is_array($criterion) || !isset($criterion['description'], $criterion['levels']) ||
                    !is_array($criterion['levels'])
            ) {
                self::fail('invalidstructure');
            }
            $criterion['description'] = self::plain_text($criterion['description']);
            $count = count($criterion['levels']);
            if ($criterion['description'] === '' || $count < 2 || $count > self::MAX_LEVELS) {
                self::fail('rubriclimits');
            }
            $cells += $count;
            $scores = [];
            foreach ($criterion['levels'] as &$level) {
                if (!is_array($level) || !isset($level['definition'], $level['score'])) {
                    self::fail('invalidstructure');
                }
                $level['definition'] = self::plain_text($level['definition']);
                $level['score'] = self::score($level['score']);
                $key = number_format($level['score'], 5, '.', '');
                if (isset($scores[$key])) {
                    self::fail('errorrepeatedscores');
                }
                $scores[$key] = true;
            }
            unset($level);
        }
        unset($criterion);
        if ($cells > self::MAX_CELLS) {
            self::fail('rubriclimits');
        }
        // Notices are identifiers, never caller-provided HTML or uploaded text.
        foreach ($parsed['notices'] as $notice) {
            if (!in_array($notice, ['noticequalitative', 'noticegradingform'], true)) {
                self::fail('invalidstructure');
            }
        }
        return $parsed;
    }

    /**
     * Parse RFC-style CSV with an explicit escape argument (PHP 8.4 compatible).
     *
     * @param string $content CSV bytes
     * @return array Normalised rubric
     */
    private static function parse_csv(string $content): array {
        $handle = fopen('php://memory', 'w+');
        if ($handle === false) {
            self::fail('csvinvalid');
        }
        try {
            fwrite($handle, $content);
            rewind($handle);
            $header = fgetcsv($handle, null, ',', '"', '');
            if ($header === false) {
                self::fail('csvempty');
            }
            $header = array_map(static fn($name) => strtolower(trim((string)$name)), $header);
            $required = ['criterion', 'level', 'level_description', 'score'];
            if (count($header) !== 4 || count(array_unique($header)) !== 4 || array_diff($required, $header)) {
                self::fail('csvmissingcolumns');
            }
            $columns = array_flip($header);
            $criteria = [];
            $indexes = [];
            $levelkeys = [];
            $rownum = 1;
            $cells = 0;
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rownum++;
                if ($rownum > self::MAX_CELLS + 101) {
                    self::fail('rubriclimits');
                }
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== 4) {
                    self::fail('csvbadrow', $rownum);
                }
                $criterion = self::plain_text($row[$columns['criterion']]);
                $level = self::plain_text($row[$columns['level']]);
                $description = self::plain_text($row[$columns['level_description']]);
                if ($criterion === '' || $level === '') {
                    self::fail('csvbadrow', $rownum);
                }
                // Prefix keys so numeric names cannot be converted to integer array keys.
                $criterionkey = 'c:' . $criterion;
                if (!isset($indexes[$criterionkey])) {
                    $indexes[$criterionkey] = count($criteria);
                    $criteria[] = ['description' => $criterion, 'levels' => []];
                }
                $index = $indexes[$criterionkey];
                $levelkey = 'l:' . $level;
                if (isset($levelkeys[$index][$levelkey])) {
                    self::fail('duplicatelevel', $rownum);
                }
                $levelkeys[$index][$levelkey] = true;
                $criteria[$index]['levels'][] = [
                    'definition' => $level . ($description !== '' ? "\n" . $description : ''),
                    'score' => self::score($row[$columns['score']]),
                ];
                $cells++;
                if (
                    count($criteria) > self::MAX_CRITERIA || count($criteria[$index]['levels']) > self::MAX_LEVELS ||
                        $cells > self::MAX_CELLS
                ) {
                    self::fail('rubriclimits');
                }
            }
            return [
                'name' => get_string('importedcsvrubricdefaultname', 'local_turnitinrubricimporter'),
                'description' => get_string('importedfromcsv', 'local_turnitinrubricimporter'),
                'criteria' => $criteria,
                'notices' => [],
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse the JSON RBC schema supported by the original importer.
     *
     * @param string $content JSON bytes
     * @return array Normalised rubric
     */
    private static function parse_rbc(string $content): array {
        try {
            $decoded = json_decode($content, true, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            self::fail('rbcinvalidjson');
        }
        if (!is_array($decoded)) {
            self::fail('invalidstructure');
        }
        $rubrics = self::records($decoded['Rubric'] ?? null, 1);
        $rubric = reset($rubrics);
        $method = $rubric['scoring_method'] ?? null;
        if (!is_int($method) && !(is_string($method) && preg_match('/^[0-5]$/D', $method))) {
            self::fail('rbcunsupported');
        }
        $type = match ((int)$method) {
            0 => 'qualitative',
            1, 2, 3 => 'standard',
            4 => 'custom',
            5 => 'gradingform',
            default => self::fail('rbcunsupported'),
        };
        $criteria = self::sort_records(self::records($decoded['RubricCriterion'] ?? null, self::MAX_CRITERIA));
        $result = [
            'name' => self::plain_text($rubric['name'] ?? '') ?:
                get_string('importedrubricdefaultname', 'local_turnitinrubricimporter'),
            'description' => get_string(
                'importedfromturnitin',
                'local_turnitinrubricimporter',
                get_string('rbctype' . $type, 'local_turnitinrubricimporter')
            ),
            'criteria' => [],
            'notices' => [],
        ];
        if ($type === 'gradingform') {
            $result['notices'][] = 'noticegradingform';
            foreach ($criteria as $criterion) {
                $value = isset($criterion['value']) ? self::score($criterion['value']) : 1.0;
                if ($value <= 0) {
                    $value = 1.0;
                }
                $result['criteria'][] = [
                    'description' => self::criterion_text($criterion),
                    'levels' => [
                        ['definition' => get_string('gradingformgeneratedlevelzero', 'local_turnitinrubricimporter'), 'score' => 0],
                        [
                            'definition' => get_string('gradingformgeneratedlevelcomplete', 'local_turnitinrubricimporter'),
                            'score' => $value,
                        ],
                    ],
                ];
            }
            return $result;
        }
        // Some exports contain one unused placeholder column in addition to the real levels.
        $scales = self::sort_records(self::records($decoded['RubricScale'] ?? null, self::MAX_LEVELS + 1));
        $cells = self::records($decoded['RubricCriterionScale'] ?? null, self::MAX_CELLS + self::MAX_CRITERIA);
        $criterionids = array_fill_keys(array_map(static fn($row) => self::identifier($row['id'] ?? null), $criteria), true);
        $scaleids = array_fill_keys(array_map(static fn($row) => self::identifier($row['id'] ?? null), $scales), true);
        $cellmap = [];
        foreach ($cells as $cell) {
            $criterionid = self::identifier($cell['criterion'] ?? null);
            $scaleid = self::identifier($cell['scale_value'] ?? null);
            if (!isset($criterionids[$criterionid], $scaleids[$scaleid]) || isset($cellmap[$criterionid][$scaleid])) {
                self::fail('invalidstructure');
            }
            $cellmap[$criterionid][$scaleid] = $cell;
        }
        $scales = array_values(array_filter($scales, static function ($scale) use ($criteria, $cellmap): bool {
            $name = strtolower(self::plain_text($scale['name'] ?? ''));
            if (!in_array($name, ['', 'empty scale'], true) || self::score($scale['value'] ?? 0) !== 0.0) {
                return true;
            }
            foreach ($criteria as $criterion) {
                $cell = $cellmap[self::identifier($criterion['id'])][self::identifier($scale['id'])] ?? [];
                if (self::plain_text($cell['description'] ?? '') !== '' || self::score($cell['value'] ?? 0) !== 0.0) {
                    return true;
                }
            }
            return false;
        }));
        if ($type === 'qualitative') {
            $result['notices'][] = 'noticequalitative';
        }
        foreach ($criteria as $criterion) {
            $levels = [];
            $criterionid = self::identifier($criterion['id']);
            foreach ($scales as $index => $scale) {
                $scaleid = self::identifier($scale['id']);
                if (!isset($cellmap[$criterionid][$scaleid])) {
                    self::fail('rbcmissingcell');
                }
                $cell = $cellmap[$criterionid][$scaleid];
                $name = self::plain_text($scale['name'] ?? '') ?: get_string('levelfallback', 'local_turnitinrubricimporter');
                $description = self::plain_text($cell['description'] ?? '');
                if ($type === 'qualitative') {
                    // Generate one consistent ordinal scale, without mixing in incidental cell values.
                    $score = count($scales) - $index - 1;
                } else if ($type === 'custom') {
                    // A custom zero is a real score, not a missing value.
                    $score = self::score($cell['value'] ?? null);
                } else {
                    $cellvalue = isset($cell['value']) ? self::score($cell['value']) : null;
                    $scalevalue = isset($scale['value']) ? self::score($scale['value']) : null;
                    $weight = isset($criterion['value']) ? self::score($criterion['value']) : null;
                    if ($cellvalue !== null && $cellvalue > 0) {
                        $score = $cellvalue;
                    } else if ($weight !== null && $weight > 0 && $scalevalue !== null) {
                        $score = round($weight * $scalevalue / 100, 5);
                    } else if ($scalevalue !== null) {
                        $score = $scalevalue;
                    } else {
                        self::fail('invalidscore');
                    }
                }
                $levels[] = ['definition' => $name . ($description !== '' ? "\n" . $description : ''), 'score' => $score];
            }
            // List entries deliberately preserve distinct RBC criteria with identical text.
            $result['criteria'][] = ['description' => self::criterion_text($criterion), 'levels' => $levels];
        }
        return $result;
    }

    /**
     * Validate a collection and reject duplicate record IDs.
     *
     * @param mixed $records Decoded rows
     * @param int $maximum Maximum allowed count
     * @return array Rows in original order
     */
    private static function records(mixed $records, int $maximum): array {
        if (!is_array($records) || count($records) < 1 || count($records) > $maximum) {
            self::fail('invalidstructure');
        }
        $ids = [];
        foreach ($records as $record) {
            if (!is_array($record)) {
                self::fail('invalidstructure');
            }
            if (isset($record['id'])) {
                $id = self::identifier($record['id']);
                if (isset($ids[$id])) {
                    self::fail('invalidstructure');
                }
                $ids[$id] = true;
            }
        }
        return array_values($records);
    }

    /**
     * Sort records without coercing large identifiers into floating point.
     *
     * @param array $records Validated rows
     * @return array Sorted rows
     */
    private static function sort_records(array $records): array {
        usort($records, static function ($a, $b): int {
            foreach (['position', 'num'] as $key) {
                foreach ([$a, $b] as $record) {
                    if (
                        isset($record[$key]) && (!is_scalar($record[$key]) || !is_numeric($record[$key]) ||
                            !is_finite((float)$record[$key]))
                    ) {
                        self::fail('invalidstructure');
                    }
                }
                $comparison = (float)($a[$key] ?? PHP_INT_MAX) <=> (float)($b[$key] ?? PHP_INT_MAX);
                if ($comparison) {
                    return $comparison;
                }
            }
            return strnatcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
        });
        return $records;
    }

    /**
     * Produce a single criterion description.
     *
     * @param array $criterion Source row
     * @return string Plain description
     */
    private static function criterion_text(array $criterion): string {
        $name = self::plain_text($criterion['name'] ?? '') ?: get_string('criterionfallback', 'local_turnitinrubricimporter');
        $description = self::plain_text($criterion['description'] ?? '');
        return $name . ($description !== '' ? "\n" . $description : '');
    }

    /**
     * Validate a JSON reference without integer truncation.
     *
     * @param mixed $value Reference value
     * @return string Namespaced array key
     */
    private static function identifier(mixed $value): string {
        if ((!is_string($value) && !is_int($value)) || (string)$value === '' || strlen((string)$value) > 100) {
            self::fail('invalidstructure');
        }
        return 'id:' . $value;
    }

    /**
     * Validate a score against Moodle's NUMBER(10,5) field.
     *
     * @param mixed $value Source score
     * @return float Finite non-negative value
     */
    private static function score(mixed $value): float {
        if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value)) {
            self::fail('invalidscore');
        }
        $score = (float)$value;
        if (!is_finite($score) || $score < 0 || $score > 99999.99999 || $score !== round($score, 5)) {
            self::fail('invalidscore');
        }
        return round($score, 5);
    }

    /**
     * Convert imported formatting to bounded plain text, never trusted HTML.
     *
     * @param mixed $text Source text
     * @return string Plain text
     */
    private static function plain_text(mixed $text): string {
        if ((!is_string($text) && !is_int($text) && !is_float($text)) || strlen((string)$text) > self::MAX_TEXT_BYTES) {
            self::fail('invalidstructure');
        }
        $text = html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text);
        return trim(strip_tags($text));
    }

    /**
     * Raise a localised error without including uploaded text in the message.
     *
     * @param string $key Language string key
     * @param int|null $row Optional numeric row number
     * @return never
     */
    private static function fail(string $key, ?int $row = null): never {
        throw new \moodle_exception($key, 'local_turnitinrubricimporter', '', $row);
    }
}
