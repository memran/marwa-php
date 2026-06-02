---
name: marwa-support-class
description: Create small Marwa support classes that keep controllers thin. Use for validation rules, form shaping, query coordination, activity formatting, small repository helpers. Avoid service containers, framework abstractions, ORM wrappers. Use when extracting repeated logic.
---

# Skill: marwa-support-class

Goal:
Create small support classes that keep controllers thin.

Use With:

- marwa-framework
- marwa-module-author

Purpose:
Support classes exist only to reduce duplication.

Good Examples:

- validation rules
- form shaping
- query coordination
- activity payload formatting
- small repository helpers
- filter builders

Bad Examples:

- service container
- framework abstraction
- ORM wrapper
- routing abstraction
- application kernel

Rules:

- Keep classes small.
- One responsibility only.
- Module-local by default.
- Use framework services directly.
- Avoid deep inheritance.

Required Output:

1. Why support class is needed
2. Responsibility
3. Public methods
4. Usage example
5. Tests needed
