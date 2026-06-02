---
name: marwa-bug-fix
description: Fix bugs in Marwa apps without breaking framework conventions. Reproduce, find root cause, apply minimal fix, add regression test, and verify no framework rule was bypassed. Use when investigating or fixing a defect.
---

# Skill: marwa-bug-fix

Goal:
Fix bugs in Marwa apps without breaking framework conventions.

Use With:

- marwa-framework
- marwa-module-author

Process:

1. Reproduce the bug.
2. Identify affected module/files.
3. Inspect existing code pattern.
4. Find root cause.
5. Apply minimal fix.
6. Add regression test.
7. Verify no framework rule was bypassed.

Rules:

- Fix root cause, not symptoms.
- Do not rewrite unrelated code.
- Do not introduce new abstractions unless required.
- Preserve existing public API.
- Preserve existing module structure.
- Use Marwa APIs only.

Forbidden:

- Raw SQL quick fixes
- Direct PDO
- Direct superglobals
- Large refactor during bug fix
- Business logic in controller

Required Output:

1. Bug summary
2. Root cause
3. Files affected
4. Fix plan
5. Code changes
6. Regression test
7. Risk notes
