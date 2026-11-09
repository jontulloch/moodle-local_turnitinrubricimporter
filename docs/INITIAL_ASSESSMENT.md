# Initial assessment — 9 November 2026

Baseline established before code changes. All 12 source files in the uploaded ZIP match repository `jontulloch/moodle-local_turnitinrubricimporter` at commit `2cb2e320310a1c9187f2ac08dc40685f16ab3633` byte for byte. This assessment describes that baseline, not the corrected candidate.

## Identity, compatibility and inventory

| Item | Observed baseline |
| --- | --- |
| Type / component | Local plugin / `local_turnitinrubricimporter` |
| Install directory | `local/turnitinrubricimporter`; ZIP has the correct single root |
| Version / release | `2026051215` / `1.3.4`, `MATURITY_BETA` |
| Declared Moodle minimum | `2022111500`, described as Moodle 4.1; no upper support range |
| PHP | No explicit minimum; inherits Moodle requirements. Moodle 5.2 requires PHP 8.3 and also supports 8.4 |
| Database | Moodle DML; no plugin-owned tables. Writes core grading tables directly |
| Dependencies | No declared external plugins, libraries or services; runtime dependency on core advanced grading and rubric grading form |
| Capabilities | One system-context import capability defined, but never enforced |
| Tasks / observers / web services | None |
| Privacy | Null provider; core-owned rubric definitions include staff user IDs and uploaded free text |
| Tests / CI | None |
| Documentation | `readme.txt` only in ZIP; repository adds GPL LICENSE and an 89-byte README |

Files examined: `import.php`, `import_form.php`, `initimport.php`, `lib.php`, `samplecsv.php`, `settings.php`, `version.php`, `db/access.php`, `classes/output/renderer.php`, `classes/privacy/provider.php`, and the complete English language file and readme. The separate form and renderer are not used by the active import flow.

## Existing behaviour

`lib.php` runs a legacy footer callback. On two grading pages it reads a grading area, checks the core manage-grading-forms capability, and injects an action through inline JavaScript matching English link labels. Outside those paths it returns before querying the database. The fallback panel is removed if matching fails.

`initimport.php?cmid=...` requires login and the core capability, then creates a grading area or switches its active method to rubric on GET, even before rejecting an existing rubric. It uses database writes rather than the grading manager.

`import.php` requires a caller-provided area ID, context ID and local return URL. It checks the core capability in the context, but does not bind the area to that context. A Moodle form accepts any file type without a plugin size limit. The parser supports CSV and JSON RBC exports and guesses format from content. It supports standard, custom, qualitative and grading-form RBC shapes, generating scores or two-level criteria where required. It stores all descriptions with `FORMAT_PLAIN` and rejects repeated numeric scores per criterion. Successful imports immediately create a ready definition, criteria and levels through separate database writes. The original draft upload is left to Moodle cleanup. The custom capability and all four admin validation settings are unused.

No code calls Turnitin, HTTP clients, shell commands, dynamic includes from user input, or PHP deserialisation. No submissions, student grade values or feedback are read or written by the plugin.

## Findings and required changes

| ID | Severity | Status / evidence | Required action |
| --- | --- | --- | --- |
| S01 | High | Confirmed: `import.php` authorises `contextid` separately from the target `areaid` | Derive and bind module, context, component and grading area; reject mismatches before any write |
| S02 | High | Confirmed: `initimport.php:47–57` changes grading method on GET without sesskey | Make GET read-only; changes only after validated POST and Moodle session-key validation |
| S03 | Medium | Confirmed: plugin import capability declared but never checked | Enforce both plugin and core capabilities at module context, including UI and sample download |
| S04 | High | Confirmed: independent writes and pre-write existence check; no transaction or lock | Use core grading controller, delegated transaction, lock and an in-lock recheck; preserve existing definitions and grading data |
| S05 | Medium | Confirmed: filepicker accepts `*`, maxbytes 0; JSON and CSV have no parser workload caps | Bound upload bytes, JSON depth, criteria, levels, cells, row and text sizes; reject invalid shapes and numeric values |
| Q01 | High | Confirmed: positive-value scoring fallback can replace a custom zero score; criteria keyed by text overwrite duplicates | Preserve explicit zero, reject unsupported scoring methods, preserve same-text RBC criteria, validate values at database precision |
| Q02 | Medium | Confirmed: missing scale structures silently interpreted as grading forms; unknown methods interpreted as standard | Validate schema and references; fail explicitly instead of silently changing rubric semantics |
| Q03 | Medium | Confirmed: caller can switch a used non-rubric grading method | Reject replacement of an existing definition of any method; check on each write |
| C01 | Medium | Confirmed: legacy footer callback, inline script, English-text DOM matching and duplicate dead classes | Use supported output hook and standard native markup, no injected script; retain a read-only legacy entry URL |
| C02 | Low | Confirmed: missing complete copyright/license headers, incorrect capability language-string key, stale and unused settings | Preserve original attribution; standardise headers, capability metadata and strings; remove unused controls |
| P01 | Medium | Confirmed: privacy statement says no personal data despite processing uploads and core author metadata | Declare links to core grading and user-draft processing; document export/deletion ownership, retention and no third-party transfers |
| T01 | High | Confirmed: no tests, CI, installation or upgrade evidence | Add parser, permission, context binding, persistence, rollback, duplicate and privacy regression coverage; arrange PHP matrix CI |
| A01 | Medium | Confirmed: required-field hint hidden with CSS; no accessible frontend tests | Use native form labels/errors, visible instructions and keyboard link; arrange Boost/RemUI and screen-reader checks |

Severity is contextual: these are source-confirmed flaws, not evidence of exploitation. No critical unauthenticated code-execution defect was identified. Stored-XSS exploitation is not confirmed: `FORMAT_PLAIN` is a useful existing protection. Escape parser messages explicitly and test hostile content. SQL values already use Moodle DML; no SQL injection was found. The main risk is authorisation, not SQL concatenation.

## Implementation decisions

Target the stated Moodle 5.2 environment with PHP 8.3/8.4; do not continue an untested Moodle 4.1 compatibility claim. Keep CSV and the four supported RBC conversions, the ready-definition outcome and numeric rubric scoring. Explain and explicitly acknowledge generated scoring before import. Reject unsupported or ambiguous file data instead of silently changing it. Keep bounded processing synchronous; no persistent cache, new tables, framework or external service is needed. Preserve the same component, install path and legacy entry URL for upgrades.

## Verification still required at this point

No PHP, database, PHPUnit, code-style or browser test had run at the time this assessment was written. The remaining audit records must state actual results. A real exported RBC example is not supplied: synthetic fixtures can test the inferred schema, but representative anonymised exports need staging verification. Manual keyboard, screen-reader, RemUI, blind marking, marker allocation, separate groups and feedback-release checks remain necessary in staging. Technical success is not a UK GDPR compliance certificate.

## Sources checked

- Moodle 5.2 release requirements: https://moodledev.io/general/releases/5.2
- Moodle 5.2 grading manager and rubric controller source, `MOODLE_502_STABLE`, retrieved 9 November 2026: https://github.com/moodle/moodle/tree/MOODLE_502_STABLE/public/grade/grading
- Hooks: https://moodledev.io/docs/5.2/apis/core/hooks
- CSRF policy: https://moodledev.io/general/development/policies/security/crosssite-request-forgery
- Privacy API: https://moodledev.io/docs/5.2/apis/subsystems/privacy
- The former contribution checklist explicitly marks itself legacy and points to current Marketplace guidelines: https://moodledev.io/general/community/plugincontribution/checklist
- The linked current Marketplace guidelines returned a Confluence shell without readable requirements in this environment; submission requirements must be rechecked in the actual submission UI before release.
