---
layout: default
title: Contributing
---

# Contributing

## Before You Open A PR

- Check whether the change belongs in `marwa-framework`, `marwa-module`, or `marwa-view` instead of this repo
- Keep app changes thin and configuration-driven
- Update the relevant base config file under `config/` when changing framework defaults
- Add tests for behavior changes
- Update docs when commands, config, themes, or module conventions change

## Verification

Run the relevant checks before submitting:

```bash
composer test
composer analyse
composer lint
```

Use `composer ci` when you want the full local validation chain.

## Commit Style

- Keep commit subjects short and imperative
- Prefer one logical change per commit
- Mention any framework-level follow-up explicitly if the app change exposes a missing capability

## Pull Requests

- Explain the problem and the approach
- List manual verification steps
- Include request/response examples or screenshots when output changes
