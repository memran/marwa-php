# AGENTS.md

## Identity

This repository is a Marwa Framework starter application.

Framework source of truth:

vendor/memran/marwa-framework/

Application code:

app/

Modules:

modules/

The framework owns:

- Application lifecycle
- Container
- HTTP Kernel
- Middleware
- Routing
- Console
- Validation
- Views
- Modules
- Configuration
- Database abstractions

The application owns:

- Product-specific features
- Product-specific configuration
- Product-specific modules
- Product-specific views
- Product-specific workflows

---

## Required Reading

Before any task:

1. Read AGENTS.md
2. Read ARCHITECTURE.md
3. Read MODULE_AUTHORING.md
4. Read CODING_STANDARDS.md
5. Read TESTING.md

---

## Task Routing

New feature:

- marwa-new-feature

Bug fix:

- marwa-bug-fix

Refactor:

- marwa-refactor

Review:

- marwa-code-review

Testing:

- marwa-test-writer

---

## Primary Rules

- Framework first.
- Reuse existing patterns.
- Keep application code thin.
- Keep modules self-contained.
- Do not duplicate framework features.
- Do not create local abstractions for framework behavior.
- Follow Users module conventions.
- Prefer configuration and manifests over hardcoded behavior.

---

## Before Coding

1. Understand requirement.
2. Inspect existing implementation.
3. Determine if change belongs in:
   - marwa-framework
   - marwa-module
   - marwa-view
   - this application

4. Produce implementation plan.
5. Implement.
6. Test.
7. Review.

---

## Forbidden

- Raw SQL
- Direct PDO
- Framework duplication
- Business logic in controllers
- App-side framework replacements
- Large wrapper abstractions
- Untested public behavior

---

## Definition Of Done

- Tests pass
- PHPStan passes
- Framework conventions respected
- No duplicated framework functionality
- Documentation updated if needed
