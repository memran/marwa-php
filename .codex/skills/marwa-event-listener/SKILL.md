---
name: marwa-event-listener
description: Create Marwa events and listeners only when real decoupling is justified. Prefer direct execution and ActivityRecorder first. Use for audit logging, notifications, metrics. Avoid unnecessary event chains and framework recreation.
---

# Skill: marwa-event-listener

Goal:
Create events and listeners only when real decoupling is needed.

Use With:

- marwa-framework
- marwa-module-author

Rules:

- Prefer direct execution first.
- Prefer ActivityRecorder for activity logging.
- Use events only when fan-out is justified.
- Keep listeners focused.
- Keep event payload small.

Good Examples:

- audit logging
- notifications
- metrics
- activity recording

Bad Examples:

- replacing direct service calls
- unnecessary event chains
- framework recreation

Required Output:

1. Event
2. Payload
3. Listeners
4. Registration
5. Why event is justified
6. Tests needed
