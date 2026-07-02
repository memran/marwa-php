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

1. Read this starter `AGENTS.md`
2. Read `ARCHITECTURE.md` for starter-owned architecture notes
3. Read framework core guidance when framework/module rules are needed:
   - `vendor/memran/marwa-framework/AGENTS.md`
   - `vendor/memran/marwa-framework/docs/architecture.md`
   - `vendor/memran/marwa-framework/docs/tutorials/modules.md`
   - `vendor/memran/marwa-framework/docs/recipes/testing.md`

The framework core owns the detailed architecture, module-authoring, coding,
and testing guidance. Do not expect `MODULE_AUTHORING.md`,
`CODING_STANDARDS.md`, or `TESTING.md` at the starter root.

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
