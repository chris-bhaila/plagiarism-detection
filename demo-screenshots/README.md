# Demo walkthrough screenshots

Flow: ENGL 210 - Academic Writing, "Essay 2: How Communication Technology Shapes Society".
Students upload real .docx files (fixtures/) -> queue worker runs the similarity check -> teacher reviews and releases.

| # | File | Shows |
|---|------|-------|
| 01 | 01-teacher-create-assignment-form | Teacher creating the assignment (title, description, due date, flag threshold) |
| 02 | 02-teacher-assignment-created | Course page listing the new assignment |
| 03 | 03-student-submission-form-file-selected | Student upload form with a .docx chosen |
| 04 | 04-student-submission-confirmation-pre-release | Receipt after submitting; check status "In progress" (results hidden until released) |
| 05 | 05-teacher-review-list-severity-spread | All submissions with real scores: High / High / Medium / Low (flag threshold set to 0.50) |
| 06 | 06-similarity-report-near-copy-highlighted | Near-verbatim copy: lexical 74%, semantic 99%, matched passages highlighted |
| 07 | 07-similarity-report-paraphrase-semantic-match | Paraphrase: lexical 0%, semantic 70% (semantic detection, no verbatim overlap) |
| 08 | 08-teacher-release-to-student | Teacher releases one student's result ("Released")  |
| 09 | 09-student-after-release | That student's receipt after release: "Under review by your instructor" |
| 10 | 10-analytics-dashboard | Analytics reflecting the new submissions |

`extras/` holds five optional screenshots (student dashboard, assignments list, review list at the default 0.35 threshold, submission text page, second pre-release view).

Accounts: chris@teacher / teacher; firstname.lastname@student.ics.dev / student123.
