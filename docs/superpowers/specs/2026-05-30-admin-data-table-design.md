# Admin Data Table Design

## Goal

Create one reusable admin data-table component that can be used by multiple modules without duplicating table chrome, search, filter, sort, export, columns, or pagination markup.

The component must be configurable so each module can enable only the features it needs.

## Scope

In scope:

- Shared table header and row rendering
- Optional search input
- Optional filter control
- Optional sortable headers
- Optional column visibility menu
- Optional export action
- Optional pagination footer
- Optional row actions slot
- Shared state preservation for query string values

Out of scope:

- Backend export/import implementations for modules that do not already have them
- Column chooser persistence in storage or database
- Client-side table virtualization or live AJAX pagination
- Global redesign of every admin module page

## Component Contract

The shared component will live under the admin theme views and receive a single config object plus data rows.

Expected inputs:

- `title`: page title shown in the table section
- `description`: optional helper text
- `state`: current list state containing query, filter, sort, direction, page, and optional visible columns
- `columns`: ordered list of column definitions
- `rows`: list of hydrated domain objects or view models
- `row_actions`: optional row action definitions
- `features`: flags for `search`, `filter`, `sort`, `columns`, `export`, `pagination`, `actions`
- `urls`: route targets for search, filter, export, and pagination
- `empty_state`: label and message shown when there are no rows

Each column definition will include:

- `key`: stable identifier used for sort and visibility
- `label`: header label
- `sortable`: boolean
- `visible_by_default`: boolean
- `render`: how the cell is displayed

Each row action definition will include:

- `label`
- `icon`
- `url`
- `method`
- `confirm`
- `visible_when`

## Rendering Rules

The component will render only the controls that are enabled in `features`.

If `search` is enabled:

- show a search box
- preserve current filter, sort, direction, and columns state

If `filter` is enabled:

- show a filter control beside search
- preserve search, sort, direction, and columns state

If `columns` is enabled:

- show a column visibility menu
- preserve the current query, filter, sort, and direction

If `export` is enabled:

- show an export action
- preserve the current query, filter, sort, direction, and columns

If `sort` is enabled:

- render clickable headers only for sortable columns
- show a single active sort icon beside the active sorted column
- use an up icon for ascending and a down icon for descending

If `pagination` is enabled:

- render the shared pagination footer
- preserve the current table state in all page links

If `actions` is enabled:

- render row actions in the last column
- hide actions per row when their visibility condition fails

## Data Flow

1. The controller or module repository builds the list state.
2. The module loads rows using the state.
3. The module prepares column metadata and feature flags.
4. The module passes all table data to the shared component.
5. The component renders the correct controls and table rows.
6. Pagination and toolbar links reuse the same state object so the user does not lose context.

## Module Responsibilities

Modules remain responsible for:

- loading data
- validating allowed sort fields and filters
- choosing which features are enabled
- defining row-specific actions
- deciding which columns are visible

The shared component remains responsible for:

- consistent admin table markup
- consistent theme styling
- consistent toolbar and pagination layout
- consistent sort icon and state link behavior

## Error Handling

The component should fail safely when optional values are missing.

- If `columns` is empty, render a basic fallback table header area or no table
- If `columns` is empty, render the empty state and do not render a table body
- If `rows` is empty, render the provided empty state
- If `state` is missing values, fall back to sensible defaults
- If a module passes an invalid feature flag, ignore it rather than breaking the view

Modules remain responsible for not passing unsafe sort keys or invalid action URLs.

## Testing Strategy

Add or update tests for the Users module first, because it already exercises search, sort, export, columns, and pagination.

Test coverage should assert:

- the shared data-table renders the Users list correctly
- active sort icons appear on the selected column
- search, filter, export, and pagination preserve state
- disabled features do not render controls
- a module can disable columns or export without breaking the table

Prefer behavior-level assertions through real admin page responses.

## Rollout Plan

1. Extract the shared component from the Users table markup.
2. Keep the Users module as the first consumer.
3. Migrate one more module after the Users component is stable.
4. Remove duplicated table markup from the older module views.

## Acceptance Criteria

- A single shared admin table component exists.
- At least the Users module uses it.
- Search, filter, sort, columns, export, and pagination can each be enabled or disabled per module.
- Sort direction is shown with an icon on the active header only.
- The starter remains minimal and module views become thinner than before.
