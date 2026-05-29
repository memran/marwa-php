# Admin Data Table Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace repeated admin list markup with one configurable shared data-table component that supports search, filter, sort, columns, export, actions, and pagination.

**Architecture:** Keep the module as the source of truth for data and state, and move the repeated admin table chrome into one Twig component under the admin theme. The component will render only the enabled features and will accept prebuilt URLs and column metadata so it stays generic and does not invent module-specific rules.

**Tech Stack:** PHP 8.2, Twig, marwa-framework views, existing `AdminListState` / `AdminPagination`, PHPUnit.

---

### Task 1: Add the shared admin data-table component

**Files:**
- Create: `resources/views/themes/admin/views/components/data-table.twig`
- Modify: `resources/views/themes/admin/views/components/icon.twig` only if the new component needs additional icon names or wrapper behavior

- [ ] **Step 1: Write the new shared Twig component**

```twig
{# Component contract:
   table = {
     title: string,
     description: string|null,
     features: {
       search: bool,
       filter: bool,
       columns: bool,
       export: bool,
       sort: bool,
       pagination: bool,
       actions: bool
     },
     toolbar: {
       search: { action: string, value: string, placeholder: string, aria_label: string }|null,
       filter: { label: string, current_label: string, items: list<array{label:string, href:string, active:bool}> }|null,
       columns: { enabled: bool, visible_count: int, items: list<array{label:string,key:string,checked:bool}>, action: string, reset_url: string }|null,
       actions: list<array{type:string,label:string,icon:string,href?:string,onclick?:string,title?:string,button_type?:string,disabled?:bool}>,
       export_url: string|null
     },
     columns: list<array{key:string,label:string,sortable:bool,active:bool,href?:string,hidden?:bool,align?:string}>,
     rows: list<array{
       cells: array<string, array{type:string,value?:string,href?:string,avatar?:string,meta?:string,tone?:string,icon?:string,protected?:bool,raw?:string}>,
       actions?: list<array{label:string,href?:string,icon?:string,button_type?:string,method?:string,confirm?:string,disabled?:bool,title?:string}>
     }>,
     pagination: array{summary:string,links:list<array{page:string,url:string,active:bool}>}|null,
     empty_state: array{title:string,message:string}
   }
#}
```

- [ ] **Step 2: Run a focused view render check**

Run: `composer test`
Expected: the existing suite still passes while the component is unused.

- [ ] **Step 3: Add the minimum fallback behavior**

If the component receives no rows, render the empty state and skip the table body. If a module disables a feature, the related toolbar block must not render.

---

### Task 2: Migrate the Users module to the shared component

**Files:**
- Modify: `modules/Users/Http/Controllers/UsersController.php`
- Modify: `modules/Users/Support/UserRepository.php`
- Modify: `modules/Users/resources/views/index.twig`
- Modify: `tests/Feature/AuthUsersModuleTest.php`

- [ ] **Step 1: Build a data-table payload in the Users controller**

```php
$table = [
    'title' => 'Registered users',
    'description' => 'Search, filter, sort, and review access at a glance.',
    'features' => [
        'search' => true,
        'filter' => true,
        'columns' => true,
        'export' => true,
        'sort' => true,
        'pagination' => true,
        'actions' => true,
    ],
    'toolbar' => [
        'search' => [
            'action' => '/admin/users',
            'value' => $state['query'],
            'placeholder' => 'Search anything...',
            'aria_label' => 'Search users',
        ],
        'filter' => [
            'current_label' => 'All',
            'items' => [
                ['label' => 'All', 'href' => '/admin/users?...', 'active' => true],
            ],
        ],
        'columns' => [
            'visible_count' => count($visibleColumns),
            'items' => [
                ['label' => 'Name', 'key' => 'name', 'checked' => true],
            ],
        ],
        'actions' => [
            ['type' => 'button', 'label' => 'Print', 'icon' => 'printer', 'onclick' => 'window.print()'],
        ],
        'export_url' => $exportUrl,
    ],
];
```

- [ ] **Step 2: Transform the Users list into table rows**

Render avatar, name/meta, badge cells, and row actions through a normalized row array so the Twig component does not need Users-specific conditionals.

- [ ] **Step 3: Replace the Users page table markup with the shared component**

Keep the page shell and heading in the module view, but delegate the table section to the shared component.

- [ ] **Step 4: Keep the existing Users behavior tests green**

Run `composer test` and confirm the Users CRUD, search, sort, export, and column visibility assertions still pass.

---

### Task 3: Migrate the first additional list page

**Files:**
- Modify: `modules/Activity/Http/Controllers/ActivityController.php`
- Modify: `modules/Activity/resources/views/index.twig`

- [ ] **Step 1: Port Activity to the same component contract**

Use the same shared component with `search`, `filter`, `sort`, and `pagination` enabled, but without columns or export.

- [ ] **Step 2: Keep Activity page behavior unchanged**

Confirm the activity list still renders the filter dropdown and active sort state using the new shared component.

---

### Task 4: Validate and update the graph

**Files:**
- No code files expected

- [ ] **Step 1: Run the full test suite**

Run: `composer test`
Expected: all tests pass.

- [ ] **Step 2: Run static analysis**

Run: `composer analyse`
Expected: no errors.

- [ ] **Step 3: Refresh the repository graph**

Run: `python -m graphify update .`
Expected: `graphify-out/graph.json` and `graphify-out/GRAPH_REPORT.md` are updated.

