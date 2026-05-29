# Admin Toast Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a reusable admin-only toast notification system that turns existing flash notices and validation summaries into dismissible toasts.

**Architecture:** The admin theme owns one toast host and one toast item component. A small app support helper converts session flash state into a normalized list of toast payloads so controllers can keep using the existing flash style while the layout renders consistent toast UI. The system stays admin-only and theme-local, with no new framework-level abstraction.

**Tech Stack:** Twig, Alpine.js, session flash data, PHPUnit

---

### Task 1: Add the admin toast support helper

**Files:**
- Create: `app/Support/AdminToast.php`
- Create: `app/View/Extensions/AdminToastViewExtension.php`
- Test: `tests/Unit/AdminToastTest.php`

- [ ] **Step 1: Write the failing test**

```php
public function testItNormalizesNoticeAndErrorsIntoToastItems(): void
{
    $items = AdminToast::fromSession('Saved successfully.', ['email' => ['The email field is required.']]);

    self::assertSame('success', $items[0]['tone']);
    self::assertSame('Saved successfully.', $items[0]['message']);
    self::assertSame('error', $items[1]['tone']);
    self::assertSame('The email field is required.', $items[1]['message']);
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `composer test -- --filter AdminToastTest`
Expected: FAIL because `AdminToast` does not exist yet.

- [ ] **Step 3: Write the minimal implementation**

```php
<?php

declare(strict_types=1);

namespace App\Support;

final class AdminToast
{
    /**
     * @param array<string, array<int, string>> $errors
     * @return list<array{tone:string,title:string,message:string}>
     */
    public static function fromSession(?string $notice, array $errors = []): array
    {
        $items = [];

        if (is_string($notice) && trim($notice) !== '') {
            $items[] = [
                'tone' => 'success',
                'title' => 'Success',
                'message' => trim($notice),
            ];
        }

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                if (trim($message) === '') {
                    continue;
                }

                $items[] = [
                    'tone' => 'error',
                    'title' => 'Error',
                    'message' => trim($message),
                ];
            }
        }

        return $items;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `composer test -- --filter AdminToastTest`
Expected: PASS.

### Task 2: Add reusable admin toast Twig components

**Files:**
- Create: `resources/views/themes/admin/views/components/toast.twig`
- Create: `resources/views/themes/admin/views/components/toast-host.twig`
- Modify: `resources/views/themes/admin/views/layout.twig`
- Modify: `app/View/Extensions/AdminToastViewExtension.php`

- [ ] **Step 1: Write the failing view-level test**

```php
public function testAdminLayoutRendersToastsFromFlashData(): void
{
    $page = $kernel->handle($this->request('GET', '/admin/users'));
    self::assertStringContainsString('data-admin-toast-host', (string) $page->getBody());
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `composer test -- --filter AuthUsersModuleTest`
Expected: FAIL because the toast host is not rendered yet.

- [ ] **Step 3: Add the toast item component**

```twig
{% set tone = toast.tone|default('success') %}
<div class="pointer-events-auto w-full max-w-sm rounded-[1.25rem] border bg-app-surface/95 p-4 shadow-[0_24px_60px_rgba(2,6,23,0.35)] backdrop-blur-xl">
  <div class="flex items-start gap-3">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ tone_classes[tone]|default(tone_classes.success) }}">
      {% include 'components/icon.twig' with { name: toast.icon|default('check-circle-2'), class: 'h-4 w-4' } only %}
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-bold text-app-text">{{ toast.title|default('Notice') }}</p>
      <p class="mt-1 text-sm text-app-muted">{{ toast.message }}</p>
    </div>
    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-app-border text-app-muted transition hover:text-app-text" @click="$el.closest('[data-toast-item]').remove()">
      <span class="sr-only">Dismiss</span>
      {% include 'components/icon.twig' with { name: 'x', class: 'h-4 w-4' } only %}
    </button>
  </div>
</div>
```

- [ ] **Step 4: Add the toast host and wire it into the admin layout**

```twig
{% set toasts = admin_toasts() %}
{% include 'components/toast-host.twig' with { toasts: toasts } only %}
```

```twig
<div data-admin-toast-host class="fixed right-4 top-4 z-[70] grid gap-3">
  {% for toast in toasts %}
    <div data-toast-item>
      {% include 'components/toast.twig' with { toast: toast } only %}
    </div>
  {% endfor %}
</div>
```

- [ ] **Step 5: Run the layout test again**

Run: `composer test -- --filter AuthUsersModuleTest`
Expected: PASS with the toast host visible in the rendered admin page.

### Task 3: Keep controller changes minimal and reusable

**Files:**
- Modify: `modules/Users/Http/Controllers/UsersController.php`
- Modify: `modules/Settings/Http/Controllers/SettingsController.php`
- Modify: `modules/DatabaseBackup/Http/Controllers/DatabaseBackupController.php`

- [ ] **Step 1: Reuse existing flash keys instead of introducing a new notification system**

```php
$this->flash('users.notice', 'User created successfully.');
session()->flash('errors', ['_global' => ['Choose a valid backup frequency.']]);
```

- [ ] **Step 2: Keep the controller code unchanged where possible**

```php
return $this->redirect('/admin/users');
```

- [ ] **Step 3: Only adjust views if a page needs a specific toast tone or label**

```twig
{% if notice is defined and notice %}
  {# leave the page-level notice as fallback for now #}
{% endif %}
```

- [ ] **Step 4: Run existing feature tests**

Run: `composer test -- --filter AuthUsersModuleTest`
Run: `composer test -- --filter StarterThemeRoutingTest`
Expected: PASS.

### Task 4: Final verification

**Files:**
- All modified files in this feature

- [ ] **Step 1: Run the full starter test suite**

Run: `composer test`
Expected: `OK`

- [ ] **Step 2: Run static analysis**

Run: `composer analyse`
Expected: `No errors`
