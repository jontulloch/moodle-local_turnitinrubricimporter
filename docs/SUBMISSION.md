# Moodle Marketplace submission draft

**State:** prepared text for review. No Marketplace submission or approval has been performed. This candidate remains beta pending staging acceptance and an approved release.

The old Plugins Directory checklist is explicitly marked legacy by Moodle. Its link to current Marketplace guidelines returned a Confluence shell without readable requirements during this audit; check the current form/guidelines in the actual submission session before sending. Do not treat this draft as confirmation of those requirements.

## Listing fields

| Field | Draft value |
| --- | --- |
| Name | Turnitin Rubric Importer |
| Frankenstyle component | `local_turnitinrubricimporter` |
| Plugin type | Local plugin |
| Supported Moodle version | 5.2 only for this candidate; do not add other versions without test evidence |
| Release | 1.4.0-beta.3 |
| Version build | 2026110902 |
| Maturity | Beta |
| Licence | GNU GPL v3 or later |
| Source | https://github.com/jontulloch/moodle-local_turnitinrubricimporter |
| Issues | https://github.com/jontulloch/moodle-local_turnitinrubricimporter/issues |
| Documentation | Repository README on the branch/tag matching the submitted release |
| Dependencies | Moodle Assignment and core rubric grading; no Turnitin plugin or API dependency |
| External service | None; exports are parsed locally |

## Short description

Import exported Turnitin RBC rubrics or a four-column CSV into Moodle Assignment advanced grading. Supports standard, custom, qualitative and grading-form conversions without an external API connection.

## Full description

Turnitin Rubric Importer helps assignment editors reuse exported rubric templates in Moodle Assignment. Upload an RBC JSON export or a UTF-8 CSV containing criterion, level, level_description and score. The importer creates and activates a new ready Moodle rubric for an assignment that has no grading definition or recorded grades.

Standard and custom rubric scores are converted to Moodle rubric levels. Qualitative rubrics use generated numeric scores, and grading forms become two-level criteria. These conversions approximate the original scoring model and require the editor's acknowledgement. Every imported rubric should be reviewed before marking.

Processing stays within Moodle. The plugin does not require a Turnitin plugin, API credentials, third-party service, subscription for CSV import or extra runtime library. Obtaining a Turnitin RBC export requires access to the source Turnitin service separately. The importer creates Moodle Assignment rubrics; it does not attach them to Turnitin V2 or LTI activities.

Import is restricted by both Moodle's grading-management permission and a dedicated module-context capability. Existing grading definitions and recorded grades are protected. Files are bounded to 2 MiB and rubric complexity to 100 criteria / 2,000 levels. Private source drafts are deleted after successful import. Core Moodle grading, user/file handling and configured logstores manage persisted data and retention.

## Reviewer demonstration

Use the synthetic `tests/fixtures/sample.csv`, `standard.rbc`, `custom.rbc`, `qualitative.rbc` and `gradingform.rbc`. Create a separate empty numeric Assignment for each import. Demonstrate denied student access, preserved custom zero, conversion acknowledgement, success, refusal of repeat imports and refusal of an already graded assignment. No external service credentials are needed.

## Screenshots to capture on staging

1. Advanced grading page with the native import action.
2. Upload page with visible instructions, filepicker, conversion acknowledgement and CSV link.
3. Imported standard rubric in Moodle's grading management view.
4. A clear validation error using a synthetic malformed file.

Capture real UI screenshots using synthetic courses and users only. Do not invent screenshots or include student records, private URLs, session keys or tokens. Verify the current Marketplace image specifications in the submission form.

## Release evidence still needed

- Approved staging results for anonymised real RBC files, roles and accessibility in Boost/RemUI.
- Required green CI results for the final commit and human approval of the release.
- Final tag, download URL and package checksum matching the submitted code.
- Institution's approval to distribute its changes, with the original Richard Rangel attribution retained.
- Current Marketplace form/guidelines checked in the actual authenticated submission flow.
