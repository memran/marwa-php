---
name: marwa-refactor
description: Improve existing Marwa code without changing behavior. Identify code smells and framework bypasses, refactor in small steps using Marwa APIs, keep public behavior unchanged, run or update tests. Use when restructuring or cleaning up code.
---

# Skill: marwa-refactor

Goal:
Improve existing Marwa code without changing behavior.

Use With:

- marwa-framework
- marwa-module-author
- marwa-code-review

Process:

1. Read AGENTS.md.
2. Inspect existing implementation.
3. Identify code smells.
4. Identify framework bypasses.
5. Propose safe refactor plan.
6. Refactor in small steps.
7. Keep public behavior unchanged.
8. Run or update tests.

Rules:

- Do not change business behavior.
- Do not rename public APIs unless requested.
- Do not introduce unnecessary abstractions.
- Remove duplication.
- Keep controllers thin.
- Move repeated module logic to small Support classes.
- Use Marwa APIs instead of custom code.
- Preserve module boundaries.

Forbidden:

- Big rewrite without reason
- Raw SQL replacement with more raw SQL
- New mini-framework layer
- Breaking routes
- Breaking views
- Breaking migration history

Required Output:

1. Refactor reason
2. Current problem
3. Refactor plan
4. Files changed
5. Behavior preserved
6. Tests affected
7. Risk notes
