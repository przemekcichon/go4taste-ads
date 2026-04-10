---
description: "Hard rule for code work: always present findings first, then implementation changes"
applyTo: "**/*.php, **/*.js, **/*.css, **/*.ts, **/*.tsx, **/*.jsx"
---
For any review or review+implementation workflow in this repository, enforce this order:

1. Findings first:
- List issues ordered by severity.
- Include file evidence and impact.
- Include test gaps and residual risk.

2. Changes second:
- Only after findings are presented and accepted.
- Keep edits minimal and scoped to accepted findings.
- Re-validate impacted files after edits.

Never start by proposing or applying code changes before reporting findings when the task includes review intent.
