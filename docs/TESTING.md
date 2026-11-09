# Testing, deployment and release checks

## Automated checks

The GitHub workflow runs the PHP 8.3 / 8.4 × MariaDB 10.11 / PostgreSQL 16 matrix against `MOODLE_502_STABLE`, with read-only repository permissions, pinned action commits, no deployment step and no production credentials.

With Moodle and its development dependencies configured, run from the Moodle code root:

```sh
php public/admin/tool/phpunit/cli/init.php
php vendor/bin/phpunit --testsuite local_turnitinrubricimporter_testsuite --fail-on-warning
php vendor/bin/phpunit public/privacy/tests/privacy/provider_test.php --filter '(test_null_provider|test_all_providers_compliant).*local_turnitinrubricimporter'
```

Use moodle-plugin-ci for `grunt --max-lint-warnings 0`, `phplint`, `phpcs --max-warnings 0`, `phpdoc --max-warnings 0`, `validate` and `savepoints`. There is no custom schema or upgrade savepoint to execute. Do not interpret the savepoint check's no-files result as an executed migration test. The audit records the separate old-to-new install/upgrade experiment.

Fixtures in `tests/fixtures` are synthetic. Parser tests cover all four RBC types, CSV quoting/BOM/multiline fields, duplicate text, explicit custom zero, hostile text, missing cells, invalid references, unknown methods, oversized/deep input, duplicate headers/levels and score precision. The output regression also renders a complete Boost page through the registered hook, asserts that the link is inside the main content and verifies the Local plugins administration entry. Integration tests cover authorised import, both capabilities, student and cross-course denial, target mismatch, session keys, conversion acknowledgement, repeat imports, private upload ownership, unrelated-page database cost, existing guides/grades and mid-write rollback.

## Staging acceptance — record outcome and tester

| Check | Required observation | Status |
| --- | --- | --- |
| Real RBC exports | Standard, custom, qualitative and grading-form exports from the institution reproduce intended criteria, scores, order and totals | Pending |
| Permissions | Admin/editing teacher allowed as configured; non-editing teacher, allocated marker, student, guest and unrelated-course teacher denied unless deliberately granted both capabilities | Pending |
| Blind marking | No identities, participant lists or anonymous identifiers are displayed or changed | Pending |
| Allocation and groups | Marker allocation, separate-group restrictions and group submissions remain under native Assignment access controls | Pending |
| Release and attempts | Feedback/grades and release workflow are unchanged; existing recorded grades prevent import; existing definitions and attempts are preserved | Pending |
| End-to-end upload | Select or drag a file, correct a validation error, acknowledge a conversion, cancel, import and download the sample CSV | Pending |
| Repeat/concurrent request | A duplicate import is refused; two importer requests cannot both create a rubric | Pending |
| Boost and RemUI | Native action and upload controls visible at desktop, mobile and 200–400% zoom, including Moodle installed under a URL subdirectory | Pending |
| Keyboard and screen reader | Logical focus order; labelled filepicker and checkbox; clear field errors and success notification; no keyboard traps; NVDA/JAWS or VoiceOver check | Pending |
| Language and presentation | Translated core page labels do not affect link insertion; long descriptions and special characters render as plain text | Pending |
| Privacy export/deletion | Test staff export, user draft cleanup, grading retention and log retention through the site's approved Privacy API workflow | Pending |
| Failure/recovery | Invalid files leave no area/method/definition changes; restore an assessment backup on staging to rehearse rollback | Pending |

No Behat browser run or WCAG 2.2 AA conformance assessment is claimed by the initial automated suite. The native form and hook tests do not replace these staging checks. Confirm the beta.4 import card and its icon match the other management cards. Check that the card joins their row with JavaScript, remains usable without JavaScript or a target row, wraps on small screens and preserves keyboard focus. Check icon sizing with the theme's font icons and standard image fallback. The plugin does not grade submissions; there is no student-facing feedback renderer to certify.

## Deployment and rollback

1. Approve role permissions, rubric conversions and institutional data-protection screening.
2. Back up code, database and Moodle data; rehearse on staging.
3. Replace the old directory with the complete candidate, preserving the component/path. Run the Moodle upgrade and verify the expected release and capability context.
4. Complete the table above with anonymised real exports before enabling import in production. Avoid concurrent editing of the same grading area through Moodle's native rubric editor during an import; the plugin lock coordinates importer requests only.
5. If rollback is required, restore a coherent code/database/data backup. Moodle does not support downgrading a plugin version in place. Removing the local plugin alone does not remove shared rubrics created through core grading.

## Public release gate

- Require observed green checks on the exact PR head, human review and the staging results above before merging and tagging a stable release.
- Enable default-branch protection/rulesets for required checks and PR review, secret scanning and push protection where available, vulnerability reporting and Dependabot alerts. Verify repository settings rather than assuming they are enabled.
- Keep full original attribution and GPL licensing. Confirm institutional permission to distribute the maintained changes.
- Promote the maturity/release/version only when the release evidence supports it. Publish a tag and matching ZIP; do not label a beta candidate stable.
- Recheck the live Marketplace submission guidance, screenshots and listing links, then submit the matching package. Acceptance is determined by Moodle reviewers.
