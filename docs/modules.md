---
layout: default
title: Modules
---

# Modules

The starter ships with optional, self-contained modules.

## Bundled Modules

- `Auth` owns login, logout, forgot-password, and reset-password flows
- `Users` owns the admin user table and CRUD screens
- `Activity` shows a sample module page
- `Notifications` shows a sample notifications module
- `Settings` shows a sample settings module
- `DashboardStatus` provides the dashboard status cards used by the admin home page

## Module Responsibilities

- Each module keeps its own manifest, provider, routes, views, migrations, and seeders when needed
- Module routes should be loaded through module manifests rather than duplicated in app route files
- Module migrations run automatically when the app boots modules

## Starter Behavior

- The starter seeds a default admin account on first boot when the users table is empty
- The auth module stores password reset tokens in its own table
- The users module persists the admin user records and the `last_login_at` field
