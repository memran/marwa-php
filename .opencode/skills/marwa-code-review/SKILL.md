# Skill: marwa-code-review

Goal:
Review Marwa code strictly against framework and module conventions.

Use With:

- marwa-framework
- marwa-module-author

Review Checklist:

Framework Usage:

- Uses Marwa helpers/services
- Uses framework router
- Uses framework model API
- Uses framework response/view APIs
- Does not duplicate framework behavior

Module Structure:

- Self-contained under modules/<Name>
- Manifest is complete
- Migrations are explicit in manifest
- Service provider is minimal
- Routes follow module pattern

Controller Quality:

- Thin controllers
- Validation handled clearly
- No business logic in controller
- Uses redirects/views/responses correctly

Database:

- Uses Marwa model/database APIs
- No raw SQL unless approved
- Migrations are ordered and explicit

Views:

- Uses module view namespace
- Uses existing theme/layout
- Uses CSRF helpers
- Follows Twig/Tailwind conventions

Testing:

- Tests starter-owned behavior
- Does not duplicate framework tests
- Regression tests added for fixes

Output:

- PASS or FAIL
- Critical issues
- Major issues
- Minor issues
- Required changes
- Suggested improvements
