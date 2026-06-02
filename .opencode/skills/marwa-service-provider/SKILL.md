# Skill: marwa-service-provider

Goal:
Create or review minimal Marwa module service providers.

Use With:

- marwa-framework
- marwa-module-author

Allowed Responsibilities:

- register view namespace
- register routes
- register migrations
- register translations
- register scheduled tasks
- bind small module contracts when required

Forbidden:

- business logic
- controller logic
- database queries
- workflow execution
- large container glue
- cross-module orchestration

Rules:

- Keep provider small.
- Follow UsersServiceProvider pattern.
- Register only what the module actually needs.
- Avoid service provider as dumping ground.

Required Output:

1. Provider purpose
2. Boot/register logic
3. Bindings added
4. Files affected
5. Risk notes
