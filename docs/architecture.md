---
layout: default
title: Architecture
---

# Architecture

This repository is the app-level implementation of `marwa-framework`.

## Layer Map

### `marwa-framework`

- application bootstrap
- container wiring
- environment loading
- config defaults and normalization
- HTTP kernel and middleware pipeline
- controller base helpers
- route bootstrapping
- console kernels and shared commands
- framework-level cache handling
- framework-level debug and lifecycle events

### `marwa-module`

- module manifest parsing
- module registry and lookup
- module cache
- module service provider bootstrap
- module path resolution
- module route and view registration contracts

### `marwa-view`

- Twig rendering
- namespaced view roots for modules

### This app repo

- public entrypoints
- app routes
- app controllers
- app-specific views and overrides
- environment defaults for this product
- module wiring for this product

## Current Boot Flow

1. `public/index.php` creates `Marwa\Framework\Application`
2. `Application` loads `.env` and registers core services
3. `AppBootstrapper` loads config and providers
4. `ModuleBootstrapper` loads module manifests, providers, views, and routes
5. `HttpKernel` runs the middleware pipeline and router dispatch
6. `ViewAdapter` resolves Twig templates and renders the response

The CLI entrypoint follows the same application bootstrap pattern through `marwa`.

## Working Rule

- Prefer `env()` and framework helpers such as `base_path()`, `storage_path()`, `cache_path()`, `resources_path()`, and `module_path()`
- Keep controller code thin and extend `Marwa\Framework\Controllers\Controller`
- Keep app routes declarative and avoid bootstrapping framework behavior inside route files
- Keep caches under `storage/`
- Treat repeated app-side convenience code as a candidate for framework support
