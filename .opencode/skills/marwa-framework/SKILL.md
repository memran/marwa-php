---
name: marwa-framework
description: Force all Marwa coding to follow framework architecture, APIs, standards, and conventions. Use existing Marwa services instead of generic PHP. Apply PSR-1/4/7/11/12/14/15/16/17/18, KISS, DRY, SOLID, DDD, and hexagonal architecture. Use for every Marwa coding task.
---

# Skill: marwa-framework

Goal:
Force all coding to follow Marwa framework architecture, APIs, standards, and conventions.

Use This Skill For:

- Every Marwa coding task
- New feature
- Bug fix
- Refactor
- Review
- Test writing
- Module creation
- Documentation

Primary Rule:
Never write generic PHP when Marwa framework APIs already exist.

Before Any Coding:

1. Read AGENTS.md
2. Inspect existing framework APIs
3. Inspect existing module structure
4. Identify correct Marwa services, contracts, helpers, providers, routes, requests, responses, database abstractions, events, middleware and testing tools
5. Produce an API usage plan
6. Only then write code

Mandatory Standards:

- PHP 8.2+
- PSR-1
- PSR-4
- PSR-7
- PSR-11
- PSR-12
- PSR-14
- PSR-15
- PSR-16
- PSR-17
- PSR-18
- KISS
- DRY
- SOLID
- DDD
- Hexagonal Architecture
- Convention over configuration

Mandatory Marwa APIs:

- Router
- Container
- Config
- Request
- Response
- Middleware
- Service Provider
- Event Dispatcher
- Cache
- Logger
- Validation
- Database abstraction
- Migration system
- Console command system
- View system
- Translation system

Forbidden:

- Raw SQL unless explicitly approved
- Direct PDO
- mysqli
- Superglobals in application code
- echo/header inside controllers
- Business logic inside controllers
- Custom router
- Custom container
- Custom response emitter
- Duplicating Marwa core features
- Hardcoded paths
- Hardcoded credentials
- Framework bypassing

Controller Rules:

- Controller must be thin
- Controller only accepts request
- Controller validates input through request/validator
- Controller calls action/service
- Controller returns Marwa response/view/json
- No business logic inside controller

Service Rules:

- Business logic belongs in application services/actions/use-cases
- Services depend on contracts, not concrete infrastructure
- Use dependency injection
- Keep methods small and focused

Repository Rules:

- Repository interface belongs in Domain
- Repository implementation belongs in Infrastructure
- Use Marwa database/query abstraction
- Do not use raw SQL by default
- Do not expose database details to Application layer

Module Rules:

- One module equals one business capability
- Module must register through service provider
- Module must expose contracts where needed
- Module must avoid direct dependency on other modules
- Use events/contracts for cross-module communication

Testing Rules:

- Add tests for new behavior
- Add regression tests for bug fixes
- Test services/actions separately from controllers
- Avoid testing framework internals
- Test business behavior

Output Format:

1. Task understanding
2. Marwa APIs discovered
3. Selected Marwa APIs
4. Architecture plan
5. Files to modify
6. Implementation
7. Tests
8. Review checklist

Review Checklist:

- Uses Marwa APIs
- No raw SQL
- No direct PDO
- No duplicated framework feature
- Thin controller
- Business logic in service/action
- Contracts respected
- PSR compliant
- Tests added
- Documentation updated if needed
