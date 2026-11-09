# Technical audit and verification — 9 November 2026

## Outcome and scope

The supplied beta release 1.3.4 (`2026051215`) required authorisation and assessment-integrity fixes before wider use. Candidate **1.4.0-beta.1 (`2026110900`)** implements those fixes and targets **Moodle 5.2 / PHP 8.3–8.4**. Local verification used Moodle **5.2.4+ (Build: 20261030)**, core commit `28fdef85e7043c8dd758af8c1a146da1419db0d0`, PHP **8.3.6**, MariaDB **10.11.14** and PHPUnit **11.5.55**. No production site, real student records or Turnitin service was accessed.

The baseline ZIP and repository matched exactly at commit `2cb2e320310a1c9187f2ac08dc40685f16ab3633`. [INITIAL_ASSESSMENT.md](INITIAL_ASSESSMENT.md) was recorded before implementation. Source inspection covered every supplied file. Review distinguished source-confirmed flaws from exploitation hypotheses and untested deployment behaviour.

This is a staging candidate, not a UK GDPR or WCAG compliance certificate, Marketplace approval or production capacity guarantee. [TESTING.md](TESTING.md) lists the remaining real-export, theme, accessibility and assessment-workflow checks. Review the live checks attached to the candidate PR before merging; a configured CI job is not an observed pass.

## Security and privacy findings

| ID | Severity | Supporting baseline evidence | Correction | Verification result |
| --- | --- | --- | --- | --- |
| S01 | High | `import.php:23–31` authorises caller context separately from destination area; writes later use area ID without a binding check | `classes/local/target.php` resolves and binds area, module context, `mod_assign` component and `submissions` area; both capabilities checked in the destination | Cross-course/mismatched-target regressions pass; actual HTTP foreign-area and unrelated-course attempts rejected |
| S02 | High | `initimport.php:47–57` creates an area or changes its active method on GET; no session-key check | Legacy endpoint only authenticates and redirects. The import endpoint requires validated POST; the service also verifies the Moodle session key | Read-only resolution test passes; actual GET creates no area; actual invalid-key POST creates no area |
| S03 | Medium | Import capability in `db/access.php` is never used in either endpoint or the injected button | Enforce local import plus core manage-grading-forms capabilities at module context for upload, sample and action visibility | Separate custom/core capability-denial tests, student denial and unrelated-course tests pass |
| S04 | High | `import.php:740–795` inserts definition, criteria and levels separately without transaction/lock and checks existence earlier | Use supported manager/controller inside delegated transaction and per-assignment lock; re-read target and existing definitions after acquiring lock | Mid-level-write failure rolls back area, method, definition, criteria and levels; second import preserves first definition |
| S05 | Medium | Any extension accepted with no plugin byte/workload limit; unrestricted full-file decode | Restrict extensions, UTF-8, bytes, JSON depth, shape, criteria, levels, cells, text, IDs, references and numeric database precision | Boundary/shape/numeric and real stored-file-size tests pass; private draft ownership tested |
| Q01 | High | RBC custom zero enters a positive-value fallback; criteria keyed by text overwrite previous records | Preserve explicit custom zero; use criterion list entries with distinct IDs; validate distinct finite scores at five decimals | Custom-zero and duplicate-text tests pass; all four synthetic RBC conversions pass |
| Q02 | Medium | Missing scale arrays become grading forms; unknown methods become standard rubrics | Require supported scoring method and complete references; only ignore genuinely unused, zero-valued placeholder scales | Malformed/unknown/missing/duplicate reference tests pass; placeholder test confirms scored content is not discarded |
| Q03 | Medium | Setup changes active method before checking existing rubric, including a marking guide | Do not modify any existing definition; additionally refuse recorded non-negative grades and non-positive/non-numeric assignment grading | Existing-guide and grade-zero preservation tests pass; existing definition blocked in HTTP flow |
| C01 | Medium | Legacy footer callback injects inline script and searches English UI strings | Supported `after_standard_main_region_html_generation` hook; standard link and form; no custom JavaScript/CSS or renderer override | Hook test shows native link; unrelated-page test proves zero extra DB reads/writes; code standards pass. RemUI visual check pending |
| C02 | Low | Stale duplicate form/renderer, unused score-validation settings, wrong capability string key and incomplete headers | Remove unused code/settings, correct localisation and capability definition, preserve original attribution, supply consistent metadata/docs | Moodle PHPCS/PHPDoc/structure and language checks pass; package check required for final ZIP |
| P01 | Medium | Null-provider explanation says no personal data, overlooking imported text, staff author metadata and temporary uploads | Retain the technically appropriate null provider for no **separate plugin-owned store**, with a precise explanation of core grading, user draft and logstore ownership | Plugin privacy tests and core null-provider/compliance tests pass. Institutional export/deletion/retention workflow still requires staging review |
| A01 | Medium | Required-field description hidden by inline CSS; no accessibility evidence | Native Moodle controls, labels, errors and visible instructions; avoid DOM text matching and custom focus behaviour | Form tests and real HTTP flow pass. No manual screen-reader/keyboard/WCAG conformance result claimed |
| T01 | High | No tests, CI or repeatable verification | Synthetic fixtures, regression suite, CI matrix and release/staging plan | Local results below; four-cell GitHub matrix must be read from actual PR checks |

Severity reflects plausible consequences and required privileges. No critical unauthenticated code-execution vulnerability was identified, and no exploitation history is asserted. The baseline already used parameterised Moodle DML and `FORMAT_PLAIN`; no confirmed SQL injection or stored-XSS exploit was found. Regression fixtures verify that imported markup is treated as plain text and errors do not echo uploaded descriptions. Uploaded filenames are not used to build a filesystem path or persisted as the rubric name.

## Areas examined without a confirmed exploit

- All public endpoints now authenticate against the actual module and check both capabilities. Internal classes introduce no AJAX, external service, web-service or command endpoint.
- No raw SQL with user-concatenated values, `unserialize`, shell execution, dynamic user-controlled includes, archive extraction or outbound HTTP is used at runtime. Thus no runtime SSRF/command-injection/deserialisation route was identified.
- Draft reads are constrained to the current user's context, component `user`, file area `draft` and the selected item ID; exactly one bounded file is accepted. Successful-source deletion was exercised through actual HTTP.
- Normal import errors are localised and escaped. Database/internal-path diagnostics are not echoed by the plugin as ordinary notifications. Moodle's site debugging policy still governs exception pages.
- No student names, submission identifiers, grade values, feedback, blind-marking IDs or participant lists are queried by the plugin. The only grade-table access is a parameterised existence check to prevent changing a graded assignment. No student records are exported.
- The import event is triggered transactionally, identifying the editor and new definition in the activity context; it omits filenames and rubric contents. Normal log access and retention remain with Moodle logstores.

## Privacy, assessment and institutional governance

Core `grading_definitions` holds imported text, staff creator/modifier IDs and timestamps; the rubric controller owns criteria and levels. Core grading/privacy providers service shared records. Source files are held in private user drafts; successful imports delete the source, while failed/cancelled uploads depend on Moodle's configured cleanup. Log retention follows the configured logstore. The plugin has no own tables, file areas, preferences, external transfer, token or secret.

A metadata-only provider was rejected by Moodle's own compliance checker during development. Keeping the null-provider contract with an accurate explanation is appropriate here because storage is entirely owned by existing Moodle components, comparable to core upload/authentication components. Adding empty export/erase methods solely to satisfy a checker would misrepresent ownership. The plugin must not erase shared grading data independently of core assessment/privacy workflows.

Blind marking, marker allocation, groups, feedback release and attempt handling remain native Assignment concerns: this is a module configuration tool, not a student grading/feedback interface. Editors must hold the management capabilities, rather than merely permission to mark allocated students. Recorded grades prevent an import. The ready state and default rubric visibility remain the baseline's behaviour; review before assessment begins. Validate those statements in the actual RemUI/course configuration as listed in the staging plan.

The university should record data flows and lawful processing, verify core draft/grade/log retention and data-subject request behaviour, and have the DPO decide whether the proposed use requires a DPIA. ICO guidance makes DPIAs mandatory for processing likely to create high risk; a local rubric-template importer is not automatically such a case. Uploading student assessment data or extending this into external processing would change the assessment. Passing technical tests does not establish UK GDPR compliance.

## Performance review

No extra framework, runtime library, service, plugin table, persistent cache, scheduled task or JavaScript was introduced. Unrelated Moodle pages return from the output hook before database/configuration work. RBC cells are indexed once by criterion/scale; no database query is made while parsing or per rubric cell. CSV is parsed in a bounded in-memory stream with an explicit PHP 8.4-compatible escape argument. The validated form result is reused, avoiding another upload read/decode.

Core persistence performs one insert per criterion/level, which is proportional to the data written. Retaining that API is preferable to custom bulk writes into shared grading tables: it preserves core metadata, file-editor handling and version contracts. The new input bounds make synchronous import reasonable; a task queue/cache would add persistence, retention and complexity without demonstrated benefit. The lock waits at most five seconds. The lock coordinates importer requests; native grading-editor requests do not share it, so avoid simultaneous editing through both interfaces.

Synthetic local measurements, not production capacity figures:

| Case | CSV bytes | Baseline parse median | Candidate parse median | Candidate additional peak during parsing | Candidate core persist | DB reads | DB writes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 10 criteria × 5 levels | 1940 | 0.251 ms | 0.352 ms | 59,744 bytes | 30.791 ms | 28 | 65 |
| 100 criteria × 20 levels | 82640 | 9.284 ms | 10.617 ms | 2,027,664 bytes | 244.282 ms | 19 | 2104 |

Parsing medians are from five warmed batches (200 iterations per typical batch; 30 per maximum batch). The extracted baseline parser's fixed CSV type label was supplied literally because its old language key is absent from the candidate; conversion logic was unchanged. Additional peak is measured above the already loaded test process, not total PHP/Moodle memory. Persistence is one measured import per size inside Moodle's PHPUnit environment; cache/logstore/database load differ from production. The smaller case has more reads due to warm-up differences; the evidence supports no read query per level, not a guaranteed exact query count. Strict validation adds work and uses more transient model memory; no speedup is claimed.

Remaining scaling limits: one rubric per request, bounded 2 MiB input, core writes proportional to levels, site-specific lock backend and database/file cleanup performance. Very long text and concurrent users require representative staging load tests if they are expected. No full institution-scale load test was run.

## Verification actually completed locally

| Check | Result |
| --- | --- |
| Complete baseline inventory / comparison | All 12 ZIP files match the existing main commit |
| PHP syntax | All plugin PHP files pass with PHP 8.3.6 |
| Moodle PHPCS | Pass, zero errors and zero warnings |
| Moodle PHPDoc checker | Pass with maximum warnings set to zero |
| Plugin structure validation | Pass; `db/install.xml` and `db/upgrade.php` correctly optional because no own schema/migration |
| Upgrade savepoint validation | No relevant file; not represented as an executed migration |
| Plugin PHPUnit suite | **58 tests, 100 assertions, pass** with fail-on-warning |
| Core Privacy API checks | **2 tests, 3 assertions, pass**. Five doc-comment metadata deprecations originate in Moodle core's own test class, not the plugin |
| Private upload handling / source deletion | Form ownership/size tests and actual HTTP successful-source deletion pass |
| Actual HTTP flow | **9 checks pass**: unauthenticated login redirect; read-only legacy GET; authorised sample download; foreign-area rejection; unrelated-course rejection; invalid session key without writes; valid POST plus source deletion; repeat-definition refusal; student denial |
| Clean candidate install | Separate empty database and data directory, pass without candidate warnings |
| Upgrade from 1.3.4 | Reinstalled the exact baseline in the disposable site, then ran candidate upgrade. Version and activity capability context updated; a pre-existing grading-definition sentinel was unchanged |
| Existing assessments | Unit tests preserve a marking-guide definition, existing imported rubric and a recorded zero grade; no live assessments accessed |
| Performance | Synthetic comparison and bounded persistence measurements above; unrelated-page zero-read/zero-write assertion passes |
| Secrets / unsafe runtime calls | Reviewed source, fixtures and prepared change set; no embedded credentials, runtime external requests or institutional/student dataset found |
| Behat / real browser / screen reader / RemUI | Not executed; required staging checks remain explicit |
| PHP 8.4 / PostgreSQL 16 | CI matrix configured; consult the observed PR checks rather than inferring success from configuration |
| Marketplace review | Draft prepared; no submission or approval performed |

The initial two plugin-test failures were fixture-triggered Moodle theme initialisation during welcome-message delivery, corrected by redirecting test messages. The PostgreSQL matrix also identified that the rollback regression must disable PHPUnit's enclosing transaction to observe the importer's own rollback immediately; the corrected test additionally verifies the pre-existing assignment survives. All final plugin tests pass without notices/warnings. The temporary benchmark harness was also corrected to reset the page between simulated requests and use the baseline's fixed label; the final measurements run passes without notices. No production code was altered to silence those fixture errors.

## Publication readiness

Repository source, license, changelog, security-reporting policy, compatibility metadata, repeatable CI, installation/upgrade instructions, privacy explanation and submission text are prepared. Original Richard Rangel copyright is retained; new maintenance attribution is separate. Beta maturity remains deliberate pending anonymised real RBC exports and human staging review.

The repository was already public, and main was observed without protection at the start of review. This task prepares a review branch/PR; it does not imply that default-branch protection, secret scanning, vulnerability reporting, merging, tagging or Marketplace publication has occurred. See the concrete release gate in [TESTING.md](TESTING.md).

## Primary sources consulted

- Moodle 5.2 requirements: https://moodledev.io/general/releases/5.2
- Version-matched manager/controller/Privacy API source: https://github.com/moodle/moodle/tree/MOODLE_502_STABLE/public/grade/grading
- Hooks API: https://moodledev.io/docs/5.2/apis/core/hooks
- CSRF policy: https://moodledev.io/general/development/policies/security/crosssite-request-forgery
- Privacy API: https://moodledev.io/docs/5.2/apis/subsystems/privacy
- ICO DPIA screening: https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/accountability-and-governance/data-protection-impact-assessments-dpias/when-do-we-need-to-do-a-dpia/
- Legacy checklist and its notice pointing to current Marketplace guidance: https://moodledev.io/general/community/plugincontribution/checklist

The linked current Marketplace guidelines could not be read beyond the Confluence shell. Verify the actual current form/guidelines before submission; the legacy checklist was not treated as current approval criteria.

## Beta.2 integration correction — 9 November 2026

Demo-site screenshots showed the installed plugin absent from the Local plugins settings list and its import action missing from the grading content. The missing menu entry was explained by removal of the unused settings file. Code review confirmed that the beta.1 action depended on `after_standard_main_region_html_generation`, which Boost places outside the main content and which custom theme layouts may omit. The exact rendering path in the proprietary demo theme has not been inspected.

Beta.2 (`2026110901`) moves the action to `before_footer_html_generation`, which the core renderer dispatches when closing the page, and uses the module context already resolved by core. A read-only settings information page restores discoverability. No import permissions, source parsing or grading writes are changed by this correction. New regressions test the actual registered hook through full Boost page rendering (including its location inside the main region) and the administration tree, covering a gap in the earlier direct-hook tests. See the beta.2 PR checks for observed results; actual RemUI confirmation remains pending.

## Beta.3 card UX restoration — 9 November 2026

Following demo-site feedback on beta.2, the user requested restoration of the original matching action-card UX. Beta.3 (`2026110902`) uses the core grading renderer's `management_action_icon` with Moodle's import icon and native `actions`/`action` classes. A small Moodle AMD module relocates that existing link into the native action row using scoped structural selectors; no English-label matching or custom card CSS is used. The server-rendered card remains visible in the main content if JavaScript or the row is unavailable. A focused import link is left in place. The module is loaded only on an eligible grading-management page. Existing-definition protection and all import permissions and parsing/persistence safeguards are unchanged. Standard JavaScript lint/build is included in CI. Actual RemUI styling remains a demo-site confirmation.

## Beta.4 icon scaling correction — 9 November 2026

Demo-site evidence confirmed that the beta.3 card is placed with the native actions, but its import icon is smaller. Moodle's `pix_icon` automatically assigns `iconsize-big` to the built-in `b/` icons; `i/import` does not receive it. The native theme applies its large icon dimensions and font size through that class. Beta.4 (`2026110903`) therefore renders the import icon through the core icon API with `iconsize-big` explicitly set and retains the same link/text markup as `management_action_icon`. The class is present in the server-rendered HTML for both font and image icon systems, including the fallback without JavaScript. No new CSS, JavaScript, permissions, parsing or persistence changes are introduced. The exact appearance in RemUI still needs demo-site confirmation; the PR records the observed automated checks.
