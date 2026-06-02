# Skill: marwa-view-author

Goal:
Create Marwa module Twig views consistent with the starter UI.

Use With:

- marwa-framework
- marwa-module-author

View Rules:

- Put views under resources/views.
- Load views through module namespace.
- Reuse existing admin layout.
- Reuse shared partials.
- Use framework CSRF helpers.
- Follow existing Twig patterns.
- Follow existing Tailwind patterns.
- Keep forms explicit and readable.

Required Views:

- index/list
- create
- edit
- show when needed
- partials when repeated UI exists

Forbidden:

- new UI language
- inline business logic
- duplicated layout
- unsafe forms without CSRF
- hardcoded asset paths when helpers exist

Required Output:

1. View files
2. Layout used
3. Data required
4. Forms/actions
5. CSRF handling
6. Render tests
