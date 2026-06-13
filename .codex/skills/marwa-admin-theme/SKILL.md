---
name: marwa-admin-theme
description: Create and maintain Marwa admin themes with the standard package structure, Tailwind-based layout shell, shared components, and theme validation. Use when building or updating an admin theme for a Marwa starter app.
---

# Skill: marwa-admin-theme

Goal:
Create and maintain a minimal, production-ready Marwa admin theme package.

Use With:

- marwa-framework
- marwa-view-author
- marwa-starter

Theme Rules:

- Keep the theme package under `resources/views/themes/admin/`.
- Create only the standard package areas:
  - `manifest.php`
  - `layouts/`
  - `partials/`
  - `components/`
  - `assets/`
- Preserve the existing admin chrome:
  - sidebar
  - header
  - brand
  - search
  - footer
  - Lucide icons
- Use Tailwind utility classes for the shell when possible.
- Keep reusable colors and theme tokens in CSS variables.
- Keep shared UI primitives reusable and small.
- Use shared datatable and pagination components when the app standardizes them in `resources/views/components/`.
- Do not modify framework core, theme registry, or view engine.

Required Layouts:

- `layouts/admin.twig`
- `layouts/auth.twig`
- `layouts/blank.twig`

Required Partials:

- `partials/head.twig`
- `partials/header.twig`
- `partials/sidebar.twig`
- `partials/footer.twig`
- `partials/scripts.twig`

Required Components:

- `components/button.twig`
- `components/card.twig`
- `components/alert.twig`
- `components/input.twig`
- `components/select.twig`
- `components/table.twig`
- `components/pagination.twig`
- `components/breadcrumb.twig`

Required Assets:

- `assets/css/variables.css`
- `assets/css/layout.css`
- `assets/css/components.css`
- `assets/js/theme.js`
- `assets/images/`

Manifest Rules:

- Declare theme name, slug, version, layouts, and assets.
- Keep the manifest simple and static.
- Do not add runtime theme builders or inheritance layers.

Implementation Rules:

- Keep layouts semantic and minimal.
- Keep partials focused on one responsibility each.
- Keep components theme-friendly and reusable.
- Prefer explicit Twig variables over hidden magic.
- Avoid business logic in Twig.
- Avoid duplicating framework behavior.

Validation:

- Add or update a theme validator when the package structure changes.
- Verify manifest keys exist.
- Verify required layouts, partials, components, and declared assets exist.
- Verify the slug matches the theme folder name.

Forbidden:

- theme inheritance systems
- database-backed theme settings
- runtime compilers
- plugin systems
- framework-core changes
- heavy wrapper abstractions
- custom view-engine behavior

Required Output:

1. Theme package file tree
2. Manifest contents
3. Layouts and partials created
4. Components created
5. Asset files created
6. Validation command
7. Tests or checks run
