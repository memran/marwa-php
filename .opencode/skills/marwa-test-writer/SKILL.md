---
name: marwa-test-writer
description: Write useful tests for Marwa modules and starter behavior. Cover user-visible behavior, routes, middleware, redirects, validation, support classes, regressions. Do not test framework internals. Use when adding tests or regression coverage.
---

# Skill: marwa-test-writer

Goal:
Write useful tests for Marwa modules and starter behavior.

Use With:

- marwa-framework
- marwa-module-author

Test Only:

- user-visible behavior
- module routes
- middleware wiring
- redirects
- rendered content
- validation behavior
- starter-specific support classes
- regression bugs

Do Not Test:

- framework router internals
- ORM internals
- Twig internals
- event dispatcher internals
- Marwa core behavior already tested elsewhere

Test Types:

Feature Tests:

- route loads
- access control works
- form submit works
- validation errors appear
- success redirect works
- rendered page contains expected content

Unit Tests:

- validation helper
- form data mapper
- small support class
- activity payload formatter

Rules:

- Test behavior, not implementation.
- Keep tests readable.
- Use existing test helpers.
- Follow existing Users module tests.
- Add regression test for every bug fix.

Required Output:

1. Test plan
2. Test files
3. Test cases
4. Assertions
5. How to run tests
