---
name: marwa-model-author
description: Create or modify Marwa module models using the framework database API. Extend Marwa\\Framework\\Database\\Model, define fillable, casts, relationships, and soft deletes. Use when adding or changing model behavior.
---

# Skill: marwa-model-author

Goal:
Create or modify module models using Marwa Framework database APIs.

Use With:

- marwa-framework
- marwa-module-author

Rules:

- Models belong in Models/.
- Extend Marwa\Framework\Database\Model.
- Keep models focused on persistence concerns.
- Define fillable fields.
- Define casts.
- Define relationships.
- Define soft delete behavior when needed.
- Keep lifecycle hooks minimal.

Allowed:

- relationships
- scopes
- casts
- persistence behavior

Forbidden:

- business workflows
- service orchestration
- HTTP concerns
- UI concerns
- cross-module coordination

Model Checklist:

- table name
- fillable fields
- casts
- timestamps
- soft deletes
- indexes considered
- relationships documented

Required Output:

1. Model file
2. Fields
3. Relationships
4. Scopes
5. Casts
6. Tests needed
