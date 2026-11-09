# Turnitin Rubric Importer

Import exported Turnitin RBC rubrics or a four-column CSV into the advanced grading rubric of a Moodle Assignment. Conversion runs entirely inside Moodle: there is no Turnitin API connection, external service, API key or additional runtime library.

**Candidate release:** 1.4.0-beta.1 (`2026110900`). Target: Moodle 5.2, PHP 8.3 or 8.4. Earlier Moodle versions and Moodle 5.3 are not claimed by this candidate. Keep this beta on staging until the release checks in [docs/TESTING.md](docs/TESTING.md) are complete.

## Features

- CSV columns: `criterion,level,level_description,score`. Headers may be reordered, but must be unique and contain exactly those four names. Save files as UTF-8. Quoted commas, double quotes and multiline descriptions are supported.
- JSON RBC exports: standard, custom, qualitative and grading-form conversions supported by the supplied importer's schema. `.json` is accepted as an alternative extension for the same RBC schema; zipped or binary RBC files are not supported.
- Explicit custom zero scores are preserved. Separate RBC criterion records with identical descriptions remain separate. CSV rows with the same criterion name form one criterion.
- Qualitative rubrics receive generated descending integer scores ending at zero. Grading forms become two-level criteria (zero / criterion value, defaulting to one). These are approximations, not an exact equivalent of Turnitin's scoring model. The editor must acknowledge the conversion before import.
- A successful import creates and activates a **ready** rubric, preserving the previous release's outcome. Review every criterion, score, student visibility and zero-score option before grading. Moodle scales the rubric to the assignment maximum grade using its native grading rules.
- No replacement of an existing grading definition, including a marking guide. Assignments with a recorded non-negative grade, or without a positive numeric maximum grade, are refused.
- Module-context permissions, Moodle session-key protection, private draft-file handling, a per-assignment lock and an atomic grading-controller transaction.
- A standard Moodle import event records the staff user and new definition ID without the filename or source rubric contents.

## Installation and upgrade

1. Back up the database, code and Moodle data directory and test on a staging clone first.
2. Place the `turnitinrubricimporter` directory in Moodle's plugin directory. On Moodle 5.2 this is normally `public/local/turnitinrubricimporter` under the code root; use the site's existing local plugin location or Moodle's ZIP installer.
3. Run **Site administration → Notifications**, or `php admin/cli/upgrade.php --non-interactive` from the Moodle code root. Do not create another nested plugin directory.
4. Purge caches after the upgrade if the new import link is not visible.

The component remains `local_turnitinrubricimporter`, so this upgrades release 1.3.4 in place. There are no plugin tables or custom schema migrations. Moodle updates capability metadata and registers the output hook during the version upgrade. Existing rubrics are preserved. Remove files deleted by this release when deploying by file copy, especially the old `lib.php` and `settings.php`; replacing the directory with the complete new package is preferable to overlaying files. Legacy unused validation configuration values can remain in `config_plugins` and have no effect.

The old `initimport.php?cmid=...` bookmark still redirects to the upload page, and is now read-only. Existing valid `areaid` / `contextid` import links are validated against the actual assignment. Caller-supplied return URLs are ignored; navigation returns to that assignment's grading management page.

## Use

1. Open an ungraded Moodle Assignment with a positive numeric grade.
2. Open **Advanced grading**. Follow **Import from Turnitin or CSV**, displayed below the main grading content when no definition exists. Alternatively open `/local/turnitinrubricimporter/initimport.php?cmid=COURSEMODULEID`.
3. Choose the RBC, JSON or CSV file. The upload page includes a sample CSV download.
4. If importing a qualitative rubric or grading form, read and accept the generated-score explanation.
5. Import, then review the ready rubric in Moodle's grading management page before any marking begins.

This plugin creates a **Moodle Assignment rubric**. It does not attach a rubric to a Turnitin V2 activity or LTI tool, send rubric content to Turnitin, or alter submissions, grades, feedback release, blind-marking identifiers, marker allocation or group membership.

## Permissions

Both `moodle/grade:managegradingforms` and `local/turnitinrubricimporter:import` are required in the destination module context. Managers and editing teachers receive the import capability by default; site administrators retain normal Moodle access. Non-editing teachers, students and guests do not receive it by default. Custom roles must be reviewed by the site administrator. Granting the local capability alone does not bypass the core capability.

## Limits

A source file may be at most **2 MiB**, with **100 criteria**, **20 levels per criterion**, **2,000 total levels**, and **10,000 bytes per individual text value**. Rubric names are at most 255 characters. Scores are finite, non-negative, no greater than `99999.99999`, with at most five decimal places and distinct within each criterion. One unused zero-valued empty placeholder column in an RBC export can be ignored; meaningful scored content is never silently discarded.

Malformed shapes, unknown references, duplicate IDs, missing cells, extra CSV columns and unsupported scoring methods produce an error rather than a partial rubric. Anonymised real exports still need verification on the university's staging site: the repository fixtures are synthetic and the Turnitin format is not a stable API contract.

## Privacy and data protection

The plugin has no separate personal data store. Moodle core grading stores imported rubric text, staff author IDs and timestamps. Core user/file handling owns source draft uploads; the uploaded source is deleted after a successful import, while failed or cancelled uploads follow Moodle's existing draft cleanup. Configured Moodle logstores retain the staff import event under site policy. There is no outbound transfer by this plugin. Users should upload rubric templates without student identifiers or assessment records.

The Privacy API null-provider declaration explains this delegation. Core grading, user/file and logstore providers own export and deletion for their shared records; uninstalling the local plugin does not erase core grading definitions. Site administrators and the DPO must verify retention, lawful processing, role permissions and any relevant DPIA screening under university policy. Technical tests do not establish UK GDPR compliance.

## Development and support

- [Audit and verification](docs/AUDIT.md)
- [Initial assessment](docs/INITIAL_ASSESSMENT.md)
- [Testing and release checks](docs/TESTING.md)
- [Marketplace submission draft](docs/SUBMISSION.md)
- [Security reporting](SECURITY.md)
- [Changelog](CHANGELOG.md)
- [Repository](https://github.com/jontulloch/moodle-local_turnitinrubricimporter)
- [Issues](https://github.com/jontulloch/moodle-local_turnitinrubricimporter/issues)

GitHub Actions checks Moodle 5.2 with PHP 8.3 / 8.4 and MariaDB 10.11 / PostgreSQL 16. The workflow runs syntax, Moodle coding standards, PHPDoc, structure, PHPUnit and core privacy checks. The final audit distinguishes local results from observed CI and staging results. No production benchmark or accessibility conformance claim is made.

Original source copyright: Richard Rangel, 2025. Hardening and maintenance changes: University of East London, 2026. Original notices are preserved. Licensed under GNU GPL v3 or later; see [LICENSE](LICENSE). This project is not affiliated with or endorsed by Turnitin.
