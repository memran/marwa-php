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
