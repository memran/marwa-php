---
name: marwa-routing-controller
description: Create routes and controllers in Marwa modules using framework conventions. Put HTTP routes in routes/http.php with router groups, use thin CRUD controllers in Http/Controllers, return Marwa views or redirects. Use when adding routes or controller actions.
---

# Skill: marwa-routing-controller

Goal:
Create routes and controllers using Marwa module conventions.

Use With:

- marwa-framework
- marwa-module-author

Route Rules:

- Put HTTP routes in routes/http.php.
- Use framework router groups.
- Use stable prefixes.
- Use existing middleware.
- Keep route names consistent.
- Do not create custom router wrappers.

Controller Rules:

- Controllers live in Http/Controllers.
- Controllers must be thin.
- One controller action should have one clear responsibility.
- Use validation or support classes for repeated logic.
- Return Marwa views, redirects, or responses.

Preferred CRUD Controllers:

- IndexController
- CreateController
- StoreController
- ShowController
- EditController
- UpdateController
- DeleteController
- RestoreController

Forbidden:

- raw SQL in controller
- direct PDO
- large workflows in controller
- direct echo/header
- duplicated middleware logic

Required Output:

1. Route list
2. Controller list
3. Middleware used
4. Validation flow
5. Response flow
6. Tests needed
