---
layout: default
title: Testing
---

# Testing

This repository tests starter-specific behavior only.

## Good Coverage

- route behavior in `routes/web.php` and `routes/api.php`
- app middleware behavior
- app-specific config integration
- starter module wiring
- module-backed auth and user flows

## What Not To Test Here

- framework internals
- framework commands
- framework view and theme mechanics already covered in `vendor/memran/marwa-framework/`

## Commands

```bash
composer test
composer analyse
composer lint
composer ci
```

## Testing Rule

Prefer behavior-level assertions that exercise real requests and responses.
