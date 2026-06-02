# Skill: marwa-manifest-author

Goal:
Create or update module manifest.php.

Use With:

- marwa-framework
- marwa-module-author

Required Fields:

- name
- slug
- version
- providers
- paths
- routes

Optional:

- migrations
- tasks

Rules:

- Migrations must be explicitly listed.
- Routes must be explicit.
- Providers must be explicit.
- Do not rely on discovery magic.
- Keep manifest readable.

Validation Checklist:

- slug unique
- version present
- migrations listed
- routes listed
- providers listed
- tasks valid

Required Output:

1. Manifest content
2. Validation summary
3. Files referenced
4. Registration summary
