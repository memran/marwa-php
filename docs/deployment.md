---
layout: default
title: Deployment
---

# Deployment

## Runtime

- Set the web server document root to `public/`
- Keep `.env` out of version control
- Use framework cache commands when you need to warm or clear config and route caches

## Assets

- Run `npm run build` for production CSS
- The build output lands in `public/assets/css/app.css`

## Docker

- Use the Docker Compose stack for local container-based development
- The app container is prewired for the `mariadb` service host

## Release Checks

- `composer ci`
- asset build
- final smoke test of the admin login and dashboard routes
