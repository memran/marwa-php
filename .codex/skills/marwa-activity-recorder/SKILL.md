---
name: marwa-activity-recorder
description: Record user activity consistently in Marwa modules using the framework ActivityRecorder. Use when logging business-relevant actions like user creation, role changes, invoices, suspensions. Avoid custom event chains and duplicate entries.
---

# Skill: marwa-activity-recorder

Goal:
Record user activity consistently.

Use With:

- marwa-framework
- marwa-module-author

Rules:

- Prefer ActivityRecorder over custom event chains.
- Keep activity payload small.
- Store meaningful actions only.
- Avoid duplicate activity entries.
- Record business-relevant events.

Examples:

- user created
- role changed
- invoice generated
- customer suspended

Required Output:

1. Activity action
2. Payload
3. Recording location
4. Tests needed
