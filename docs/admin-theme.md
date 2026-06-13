# Admin Theme Tutorial

This starter ships a reusable admin theme package under `resources/views/themes/admin/`.
The goal is to keep the theme structure consistent while keeping module views, controllers, and shared components separate.

Use this guide when you want to understand how the admin UI is assembled, where to add new UI pieces, and how to validate the theme package.

## Theme Layout

The admin theme is organized into five parts:

- `manifest.php`
- `layouts/`
- `partials/`
- `components/`
- `assets/`

That structure keeps the theme predictable and easy to extend without adding extra theme abstractions.

### Manifest

The manifest defines the theme metadata and declared assets.

Path:

- `resources/views/themes/admin/manifest.php`

It should include:

- `name`
- `slug`
- `version`
- `layouts`
- `assets`

The current admin manifest points to:

- `layouts/admin.twig`
- `layouts/auth.twig`
- `layouts/blank.twig`

and declares the CSS and JS bundles that the theme uses.

### Layouts

Layouts define the page shell.

Use them for:

- authenticated admin pages
- auth pages like login and reset password
- blank utility pages

Current layouts:

- `layouts/admin.twig`
- `layouts/auth.twig`
- `layouts/blank.twig`

The authenticated layout includes the admin sidebar, header, footer, breadcrumbs, toasts, and scripts.

## Partials

Partials hold the shared page fragments used by the layouts.

Current partials:

- `partials/head.twig`
- `partials/header.twig`
- `partials/sidebar.twig`
- `partials/footer.twig`
- `partials/scripts.twig`

Use partials for:

- meta tags and CSS loading
- top navigation
- sidebar brand and menu
- footer text
- JavaScript loading

Keep partials small and explicit. If a fragment starts growing too large, move repeated markup into a component.

## Components

Components are reusable view blocks used by multiple pages and modules.

Current shared admin components include:

- `components/button.twig`
- `components/card.twig`
- `components/alert.twig`
- `components/input.twig`
- `components/select.twig`
- `components/table.twig`
- `components/breadcrumb.twig`
- `components/icon.twig`
- `components/toast.twig`
- `components/toast-host.twig`
- `components/activity-log.twig`
- `components/permission-panel.twig`
- `components/tabs.twig`
- `components/form-field.twig`
- `components/status-card.twig`
- `components/status-badge.twig`
- `components/search-bar.twig`
- `components/widget.twig`

The datatable and pagination UI are shared components too:

- `resources/views/components/data-table.twig`
- `resources/views/components/pagination.twig`

Use the shared component tree for logic that is reused across modules.

### Datatable Usage

The datatable component expects a `DataTableResult` object.

Example:

```twig
{% include '@Shared/components/data-table.twig' with { table: table } only %}
```

In a controller:

```php
$table = UserDataTable::make($request)
    ->paginate(20)
    ->result();

return view('admin/users/index', [
    'table' => $table,
]);
```

### Pagination Usage

The pagination component expects a `PaginationResult` object.

Example:

```twig
{% include '@Shared/components/pagination.twig' with { pagination: pagination } only %}
```

## Assets

Theme assets live under:

- `resources/views/themes/admin/assets/`
- `public/themes/admin/`

The theme uses utility CSS classes and shared asset bundles.

Important asset files:

- `assets/css/variables.css`
- `assets/css/layout.css`
- `assets/css/components.css`
- `assets/js/theme.js`

The public asset tree mirrors the theme asset tree so the browser can load the files directly.

## How To Use The Theme From A Module

Follow the module pattern used by `Users` and `Dashboard`:

1. Put the module views under `modules/<Name>/resources/views/`
2. Register the module view namespace in the module service provider
3. Extend `layouts/admin.twig` for authenticated pages
4. Use shared components instead of copying theme markup
5. Keep controllers thin and pass view data from the module layer

Example module view:

```twig
{% extends "layouts/admin.twig" %}

{% block title %}Users{% endblock %}

{% block content %}
    {% include 'components/card.twig' with {
        title: 'Users',
        content: 'Module content goes here'
    } %}
{% endblock %}
```

## Theme Validation

Validate the admin theme package with:

```bash
php marwa theme:validate admin
```

The validator checks:

- `manifest.php` exists
- the manifest returns an array
- `name`, `slug`, and `version` are present
- the slug matches the folder name
- required layouts exist
- required partials exist
- required components exist
- declared CSS and JS assets exist

Example success:

```text
Validating theme: Admin Default
[OK] Manifest exists
[OK] Required layouts exist
[OK] Required partials exist
[OK] Required components exist
[OK] Declared assets exist
Theme "admin" is valid.
```

## Practical Notes

- Keep theme markup in the theme package.
- Keep module pages in module directories.
- Keep shared UI pieces in `resources/views/components/`.
- Prefer utility classes and the existing Tailwind-based shell over new theme-specific CSS names.
- Avoid adding new theme inheritance or runtime theme builders.

## Troubleshooting

If a page 500s after a theme change:

- confirm the module view namespace is registered
- confirm the template path exists in the correct module or shared component tree
- confirm the theme asset URLs exist in `public/themes/admin/`
- run `php marwa theme:validate admin`
- rerun the focused feature test for the affected module

If a component does not render:

- check the include path
- check the variable name passed to the component
- check whether the page is using the shared component or a module-local copy

