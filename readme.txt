Plugin: Turnitin Rubric Importer
====================================

This is an adapted version of the provided local_turnitinrubricimporter importer.

What it does
------------

- Adds an upload page for Turnitin .rbc rubric exports and supported CSV rubric files
- Parses standard Turnitin RBC files that contain:
  - Rubric
  - RubricCriterion
  - RubricScale
  - RubricCriterionScale
- Creates a Moodle Assignment advanced grading rubric
- Keeps the same component name and URLs as the supplied importer:
  local_turnitinrubricimporter

Important notes
---------------

- Supports Turnitin standard, custom, qualitative, and grading form RBC exports.
- Supports CSV files with columns: criterion, level, level_description, score.
- Qualitative rubrics without numeric scores are given generated Moodle scores and a notice is displayed.
- Turnitin grading forms are converted into compatible two-level Moodle rubrics and a notice is displayed.
- Rubric descriptions are marked as imported from the detected Turnitin type or CSV.
- The upload page includes a CSV example and a sample CSV download.
- The plugin will not import over an existing rubric for the same grading area.

Installation
------------

Place the turnitinrubricimporter folder inside /local/ and run the Moodle upgrade.

If you already use the original CSV importer with the same component name, install this over it as an upgrade on a test site first.

Suggested test path
-------------------

1. Install on a copy of Moodle first
2. Purge all caches
3. Open an Assignment
4. Ensure advanced grading is not already configured with a rubric
5. Go to:
   /local/turnitinrubricimporter/initimport.php?cmid=COURSEMODULEID
6. Upload a standard Turnitin .rbc file
7. Review the draft rubric in Moodle's grading form manager


Version 1.1.2 moves the injected import panel into the main page body and stores imported RBC rubric text as plain text to avoid visible formatting markers.


1.1.3-rbc-card: Displays the importer as a third card beside Moodle rubric setup actions where available.


1.1.4-rbc-card-align: Uses Moodle's native action card classes so the Turnitin RBC card aligns with the existing rubric cards.


1.2.0-rbc-csv-importer: Adds CSV support using columns criterion, level, level_description, score and renames the course-page card to Import from Turnitin or CSV.


2026051213: Simplified import page layout to use Moodle's native filepicker styling with a single 900px content wrapper.


1.3.4
- Successful imports are created as ready Moodle rubric definitions rather than draft definitions.
