# Changelog

## 1.4.0-beta.2 — 9 November 2026

- Move the import action to Moodle's `before_footer_html_generation` hook, so it is inserted inside the main content rather than at a theme's end-of-body layout position.
- Use the module context already resolved by Moodle instead of depending on incoming grading-page query parameters.
- Restore a read-only information page under Local plugins for administrator discovery and usage instructions.
- Add regression checks for the registered hook through complete Boost page rendering and the administrator menu entry. Actual RemUI confirmation remains a staging check.

## 1.4.0-beta.1 — 9 November 2026

- Bind grading area, context, component and module IDs to one authorised Moodle Assignment. Enforce the plugin import capability together with the core grading-management capability.
- Make the legacy setup link read-only. Import only through a validated POST with a Moodle session key.
- Use Moodle's grading manager and rubric controller inside a transaction and per-assignment lock. Refuse existing definitions of any method and assignments with recorded grades.
- Bound uploads and parsing; validate file extension, UTF-8, JSON depth, CSV structure, record references, numeric range and database precision.
- Preserve custom zero scores and same-text RBC criteria; retain unused empty-placeholder handling; require acknowledgement of qualitative/grading-form score conversion.
- Replace English-text DOM injection and inline CSS/JavaScript with a supported output hook and native Moodle form controls.
- Delete the successful source upload, clarify core-owned privacy processing and record a minimal transactional audit event.
- Remove unused settings, duplicate form and renderer. Preserve component and valid legacy entry links; return to the actual assignment grading page.
- Add synthetic fixtures, regression tests, PHP/database CI matrix, upgrade instructions, security policy and submission documentation. Scope this candidate to Moodle 5.2 / PHP 8.3–8.4.

## 1.3.4 — supplied baseline (2026051215)

- Imported rubrics are created as ready definitions rather than draft definitions.
- Supports CSV and standard, custom, qualitative and grading-form RBC exports.

Earlier history is preserved in the Git repository. The uploaded readme contained older development notes; no release dates or compatibility results have been inferred from those notes.
