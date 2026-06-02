# Skill: marwa-database-migration

Goal:
Create or modify Marwa module database migrations safely.

Use With:

- marwa-framework
- marwa-module-author

Rules:

- Put migrations under modules/<Name>/database/migrations.
- Use explicit, ordered migration file names.
- Add every migration file path to manifest.php.
- Do not rely only on paths['database/migrations'].
- Keep migrations reversible when possible.
- Use framework migration/schema APIs.
- Do not use raw SQL unless explicitly approved.
- Do not edit already-applied migrations unless explicitly requested.

Migration Checklist:

- table name is clear
- columns are explicit
- indexes are intentional
- nullable fields are justified
- default values are safe
- timestamps included when needed
- soft delete fields included when needed
- migration registered in manifest

Seeder Rules:

- Seeders are optional.
- Use seeders only for starter bootstrap data.
- Do not add seed data for every module by default.

Required Output:

1. Migration file path
2. Schema summary
3. Manifest update
4. Seeder needed or not
5. Rollback behavior
6. Tests or verification
