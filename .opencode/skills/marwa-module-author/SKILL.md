# Skill: marwa-module-author

Goal:
Create, modify, review, refactor and maintain Marwa application modules using the repository module conventions.

Reference:
The Users module is the canonical implementation.

Primary Rule:
Do not create a mini-framework inside the application.

Before Any Change:

1. Read AGENTS.md
2. Inspect existing modules
3. Inspect Users module
4. Follow existing conventions
5. Reuse framework capabilities first
6. Produce implementation plan
7. Only then modify code

Framework First:

Always use existing Marwa features:

- env()
- config()
- base_path()
- storage_path()
- view()
- router groups
- controllers
- middleware
- framework models
- framework scheduler
- framework events
- framework migrations
- framework service providers
- framework helper functions
- marwa-support classes

Never create wrapper abstractions around existing framework features.

Module Boundary Rules:

- Module must remain self-contained.
- Everything belongs inside modules/<Name>/.
- Only create app-level code when truly necessary.
- Missing reusable capability should be treated as a framework enhancement request.

Canonical Structure:

modules/
ModuleName/
manifest.php
ModuleServiceProvider.php
routes/
http.php
Http/
Controllers/
Models/
Support/
resources/
views/
database/
migrations/
seeders/

Only create directories actually needed.

Manifest Rules:

Every module must contain:

- name
- slug
- version
- providers
- paths
- routes

Optional:

- migrations
- tasks

Migration Rules:

- Store migrations under database/migrations.
- Add every migration explicitly to manifest migrations.
- Do not rely on automatic discovery.
- Use descriptive migration names.

Service Provider Rules:

Service providers must remain minimal.

Allowed:

- view namespace registration
- route registration
- migration registration
- scheduler registration
- small boot logic

Forbidden:

- business logic
- workflow orchestration
- large container bindings

Routing Rules:

Routes belong in:

routes/http.php

Use:

- route groups
- middleware groups
- route prefixes

Keep route structure consistent with Users module.

Controller Rules:

Controllers must stay thin.

Allowed:

- read request
- validate request
- call model
- call support class
- flash messages
- redirect
- render view

Forbidden:

- business workflows
- large validation logic
- complex query construction
- cross-module orchestration

Preferred CRUD Controllers:

- IndexController
- CreateController
- StoreController
- ShowController
- EditController
- UpdateController
- DeleteController
- RestoreController

Model Rules:

Use:

Marwa\Framework\Database\Model

Models should contain:

- fillable
- casts
- soft delete settings
- persistence-related behavior

Avoid:

- application services
- cross-module logic
- framework recreation

Support Rules:

Support classes should remain small.

Good Examples:

- validation
- form shaping
- query coordination
- activity formatting
- small repositories

Bad Examples:

- service container
- custom ORM
- custom router
- application framework layer

View Rules:

Views belong in:

resources/views/

Requirements:

- use module namespace
- use existing theme
- use shared layouts
- use CSRF helpers
- follow current Twig conventions
- follow current Tailwind conventions

Database Rules:

- migrations under database/migrations
- seeders only when needed
- explicit migration registration in manifest

Events Rules:

Use events only when needed.

Prefer:

- direct ActivityRecorder usage
- direct workflow execution

Avoid:

- unnecessary listeners
- unnecessary fan-out
- framework recreation

Testing Rules:

Test only starter-owned behavior.

Write:

- feature tests
- route tests
- middleware tests
- response tests
- view tests
- support class tests

Avoid:

- ORM internal tests
- routing internal tests
- Twig internal tests
- framework behavior tests

Required Output:

1. Requirement summary
2. Existing module analysis
3. Files to create/modify
4. Implementation plan
5. Code changes
6. Tests
7. Review checklist

Review Checklist:

- Module is self-contained
- Manifest complete
- Migrations explicitly registered
- Thin controllers
- Small support classes
- Existing theme used
- Existing framework APIs used
- No framework duplication
- Tests added
- Documentation updated

When Unsure:

Copy the Users module pattern.
Never invent a new module architecture when an existing module already demonstrates the solution.
