---
name: marwa-admin-theme
description: Create and maintain Marwa admin themes with the standard package structure, Tailwind-based layout shell, shared components, dark-mode-safe tokens, and theme validation. Use when building or updating an admin theme for a Marwa starter app.
---

# Skill: marwa-admin-theme

Goal:
Create and maintain a production-ready Marwa admin theme package without breaking module boundaries, shared components, asset loading, or dark mode.

Use With:

- marwa-framework
- marwa-view-author
- marwa-bug-fix
- marwa-starter

Theme Rules:

- Keep the theme package under `resources/views/themes/<theme>/`.
- Create only the standard package areas:
  - `manifest.php`
  - `layouts/`
  - `partials/`
  - `components/`
  - `assets/`
- Preserve the admin shell behavior:
  - sidebar
  - header
  - brand
  - search
  - footer
  - notifications dropdown
  - theme toggle
  - mobile sidebar
  - authenticated user menu
  - Lucide icons
- Use Tailwind utility classes for the shell when possible, but make theme colors flow through CSS variables.
- Keep reusable colors and theme tokens in CSS variables and bridge hardcoded utilities back to those tokens where needed.
- Keep shared UI primitives reusable and small.
- Use shared components for app-wide behavior when they already exist in `resources/views/components/` or `@Shared`.
- Shared DataTable and shared Pagination are not theme-local components.
- The theme may style shared components, but must not fork or duplicate them inside the theme package.
- Do not modify framework core, theme registry, or view engine.

Boundary Rules:

- Theme code owns rendering only.
- Theme code must not introduce app data-provider classes, dashboard DTO builders, or theme-specific support classes such as `ExecutiveDashboardData.php`.
- Modules own business logic, query logic, controllers, and data preparation.
- Themes may override or provide Twig presentation fragments for module output only when the app already routes rendering through the theme.
- Do not move module business templates into the theme unless the template is explicitly part of the shell/component layer.
- Do not create module folders inside a theme such as `dashboard/` or `security/`.
- Do not keep empty theme folders or legacy folders after refactors.

Dashboard Rules:

- Dashboard data, widget registry, persistence, reorder/save/reset, and refresh endpoints stay in the Dashboard module.
- Theme owns the dashboard widget shell and styling.
- Dashboard widget rendering must stay dynamic and must consume the module-provided widget payload.
- Do not add static dashboard mock data to a live theme.
- Customize interactions must preserve the existing product behavior:
  - edit/customize toggle
  - drawer or side panel behavior
  - drag and drop in the visible dashboard grid
  - widget refresh
  - widget add/remove
  - active/inactive status indicators

Shared Component Rules:

- DataTable must remain in shared components, not in a theme package.
- Pagination must remain in shared components or the established shared layer when the app standardizes it.
- AI chat widget hooks remain shared compatibility points.
- If a theme needs custom chrome around a shared component, wrap it without duplicating the component logic.

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
- `components/breadcrumb.twig`

Optional Theme Components:

- `components/pagination.twig` only if the app has not standardized shared pagination yet.
- `components/datatable.twig` only if the app has not standardized a shared datatable yet.
- Once shared pagination or shared datatable exists, the theme must consume the shared component instead of keeping a theme-local duplicate.

Required Assets:

- `assets/css/variables.css`
- `assets/css/layout.css`
- `assets/css/components.css`
- `assets/js/theme.js`
- `assets/images/`

Public Asset Rules:

- Every declared theme asset must resolve through `theme_asset(...)`.
- Public theme wrappers under `public/themes/<theme>/` must point only to that theme's assets.
- Do not leave references to another theme such as `/themes/admin/...` inside a different theme bundle.
- If the theme uses import wrapper files in `public/themes/<theme>/css/`, verify they target the matching `../assets/...` path.
- If the project ships compiled or mirrored public assets, keep source and public wrappers in sync.

Manifest Rules:

- Declare theme name, slug, version, layouts, and assets.
- Keep the manifest simple and static.
- Do not add runtime theme builders or inheritance layers.
- Slug must match the theme folder.
- Theme type should remain consistent with app expectations for admin themes.

Implementation Rules:

- Keep layouts semantic and minimal.
- Keep partials focused on one responsibility each.
- Keep components theme-friendly and reusable.
- Prefer explicit Twig variables over hidden magic.
- Avoid business logic in Twig.
- Avoid duplicating framework behavior.
- Preserve the previous working interface unless the user explicitly asks to redesign it.
- When replacing an existing admin theme, keep feature parity before visual divergence.

Dark Mode Rules:

- Do not assume Tailwind `dark:` utilities alone are sufficient.
- Audit elements both inside and outside `.admin-content`.
- If shell chrome uses hardcoded `bg-white`, `bg-slate-*`, `text-slate-*`, `border-slate-*`, or `hover:*` utilities, add a theme token bridge so light and dark modes both render correctly.
- Check topbar, footer, search dropdowns, profile menu, notification dropdowns, drawers, and dashboard widget chrome separately.
- Avoid fixing dark mode by scattering one-off template overrides when a theme-level token bridge is the correct fix.

Auth and Admin Parity Rules:

- `layouts/auth.twig` must load the same theme asset family as the admin shell.
- Login/auth pages must not lose CSS when the theme is switched.
- Brand, logo, search box, menu, logout item, and header controls must remain visible and functional after a theme refactor.

Module Integration Rules:

- Module pages should inherit theme colors through shared token classes where possible.
- Do not hardcode module-specific colors inside module views when the theme layer can provide consistent tokens.
- Security, Users, Dashboard, and other modules must continue to render under the active admin theme without adding theme-local business code.

Validation:

- Add or update a theme validator when the package structure changes.
- Verify manifest keys exist.
- Verify required layouts, partials, base theme components, and declared assets exist.
- Verify the slug matches the theme folder name.
- Verify no empty or legacy theme folders remain after refactors.
- Verify no cross-theme asset references remain.
- If pagination or datatable are shared in this app, do not require theme-local copies in validation.

Required Checks:

- Run `php marwa theme:validate <theme>`.
- Run focused PHPUnit tests for theme package and theme routing when those tests exist.
- Run the relevant UI asset tests when theme CSS or public wrappers change.
- Grep for accidental references to another theme name after cloning or renaming a theme.
- Verify the theme works for:
  - dashboard
  - users index
  - auth/login
  - notifications/menu dropdown
  - shared datatable
  - shared pagination
- If a bug fix touched error pages, run the relevant error renderer tests.

Forbidden:

- theme inheritance systems
- database-backed theme settings
- runtime compilers
- plugin systems
- framework-core changes
- heavy wrapper abstractions
- custom view-engine behavior
- theme-owned app support classes
- static dashboard demo data in live admin themes
- copying module directories into the theme package
- duplicating shared datatable or pagination templates inside the theme
- leaving broken public asset paths
- leaving legacy code or empty folders behind after migration

Required Output:

1. Theme package file tree
2. Manifest contents
3. Boundary decisions:
   - what stays in theme
   - what stays in module/shared layer
4. Layouts and partials created or updated
5. Components created or updated
6. Asset files created or updated
7. Validation commands and tests run
8. Known risks or follow-up gaps, if any
