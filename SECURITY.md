# Security policy

This candidate targets Moodle 5.2 on supported PHP versions. Keep Moodle core and PHP patched. Release 1.3.4 has known authorisation and grading-integrity defects; use the reviewed replacement after staging approval.

Report suspected vulnerabilities privately to the repository maintainer using the university's approved contact route. If GitHub private vulnerability reporting has been enabled for this repository, use **Security → Report a vulnerability**. Do not assume that this option is enabled. Use public Issues for ordinary bugs after removing personal data and credentials.

A report should describe the affected release, required role, expected and observed behaviour, and a minimal synthetic reproduction. Do not include student work, live session keys, passwords, API credentials or a production database dump.

Successful imports use a new rubric and never replace an existing grading definition. Changes to grading methods should be tested before a course is used for assessment. Restore backups rather than directly editing student grades or core grading tables as a rollback procedure.
