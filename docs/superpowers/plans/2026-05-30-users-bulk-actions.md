# Users Bulk Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add reusable bulk delete and bulk status update controls to the shared admin data-table and wire them into the Users module.

**Architecture:** The shared `data-table.twig` component owns bulk-selection UI, the bulk toolbar, and select-all behavior. The Users controller supplies row metadata, bulk config, and dedicated POST endpoints for delete and status updates. The repository handles user lookup and mutation, while the controller keeps permission checks, activity logs, and redirect state close to the request flow.

**Tech Stack:** Twig, Alpine.js, Marwa DB ORM, PHPUnit

---

### Task 1: Extend the shared data-table component

**Files:**
- Modify: `resources/views/themes/admin/views/components/data-table.twig`

- [ ] **Step 1: Update the component contract**

```twig
{% set bulk = table.bulk|default({}) %}
{% set has_bulk = features.bulk|default(false) and (bulk.action_delete_url|default('') or bulk.action_status_url|default('')) %}
```

- [ ] **Step 2: Render bulk selection UI**

```twig
<div x-data="{ selected: [], selectableCount: {{ bulk.selectable_count|default(0) }} }">
  <form method="post" id="{{ bulk.form_id|default('bulk-table-form') }}">
    {{ csrf_field() }}
  </form>
</div>
```

- [ ] **Step 3: Render row checkboxes and bulk toolbar**

```twig
<input form="{{ bulk.form_id|default('bulk-table-form') }}" type="checkbox" name="ids[]" value="{{ row.bulk_id }}" data-bulk-row>
```

```twig
<button type="submit" formaction="{{ bulk.action_delete_url }}" name="bulk_action" value="delete">Delete selected</button>
```

- [ ] **Step 4: Run a targeted syntax sanity check**

Run: `composer analyse`
Expected: no new errors in `resources/views/themes/admin/views/components/data-table.twig` consumers.

### Task 2: Add Users bulk endpoints and table payload

**Files:**
- Modify: `modules/Users/routes/http.php`
- Modify: `modules/Users/Http/Controllers/UsersController.php`

- [ ] **Step 1: Add bulk routes**

```php
$routes->post('/users/bulk-delete', [UsersController::class, 'bulkDelete'])
    ->middleware(new RequirePermission('users.delete'))
    ->name('admin.users.bulk_delete')
    ->register();

$routes->post('/users/bulk-status', [UsersController::class, 'bulkStatus'])
    ->middleware(new RequirePermission('users.edit'))
    ->name('admin.users.bulk_status')
    ->register();
```

- [ ] **Step 2: Add bulk config to the Users table payload**

```php
'bulk' => [
    'form_id' => 'users-bulk-form',
    'action_delete_url' => '/admin/users/bulk-delete',
    'action_status_url' => '/admin/users/bulk-status',
    'selectable_count' => $this->countSelectableUsers($usersPage['data'], $currentAdminId),
],
```

- [ ] **Step 3: Add controller methods that validate ids, apply the repository changes, log activity, and redirect back to the current list state**

```php
public function bulkDelete(ServerRequestInterface $request): ResponseInterface
public function bulkStatus(ServerRequestInterface $request): ResponseInterface
```

- [ ] **Step 4: Verify the controller still builds the list view with the new bulk payload**

Run: `composer test -- --filter AuthUsersModuleTest`
Expected: the Users list still renders and bulk routes are registered.

### Task 3: Add repository helpers for bulk mutation

**Files:**
- Modify: `modules/Users/Support/UserRepository.php`

- [ ] **Step 1: Add bulk lookup and mutation helpers**

```php
/**
 * @param list<int> $ids
 * @return list<User>
 */
public function usersByIds(array $ids): array
```

```php
/**
 * @param list<int> $ids
 */
public function bulkDeleteUsers(array $ids): int
```

```php
/**
 * @param list<int> $ids
 */
public function bulkUpdateStatus(array $ids, int $isActive): int
```

- [ ] **Step 2: Reuse the existing model API instead of raw SQL**

```php
$users = User::whereIn('id', $ids)->get();
```

- [ ] **Step 3: Run the repository tests for list behavior**

Run: `composer test -- --filter UserRepositoryListingTest`
Expected: existing list/filter behavior still passes.

### Task 4: Add coverage for the bulk actions

**Files:**
- Modify: `tests/Feature/AuthUsersModuleTest.php`
- Create: `tests/Unit/UserRepositoryBulkActionsTest.php`

- [ ] **Step 1: Add feature coverage for the new POST endpoints**

```php
$response = $this->post('/admin/users/bulk-delete', [
    'ids' => [$userId],
]);
```

- [ ] **Step 2: Add repository assertions for deleting and updating multiple users**

```php
self::assertSame(2, $repository->bulkUpdateStatus([$id1, $id2], 0));
```

- [ ] **Step 3: Run the targeted tests**

Run: `composer test -- --filter AuthUsersModuleTest`
Run: `composer test -- --filter UserRepositoryBulkActionsTest`
Expected: both pass.

