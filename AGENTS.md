# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains the application code under the `App\\` namespace: controllers, listeners, jobs, middleware, mail, commands, and events.
- `config/` holds runtime configuration such as `app.php`, `view.php`, `logger.php`, and `server.php`.
- `routes/` defines HTTP routes in `web.php` and `api.php`.
- `resources/` stores Twig templates and source styles, especially `resources/views/` and `resources/css/app.css`.
- `public/` is the web root. Compiled assets are written to `public/assets/css/`.
- `database/` contains migrations and seeders. Use `.env.example` as the starting point for local environment settings.

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies and sets up autoloading.
- `php -S localhost:8000 -t public/` runs the app locally using the public directory as the document root.
- `npm run dev` watches and rebuilds Tailwind CSS from `resources/css/app.css` into `public/assets/css/app.css`.
- `npm run build` produces the minified production stylesheet.
- `vendor/bin/phpunit` runs the test suite when PHPUnit dependencies are installed.

## Coding Style & Naming Conventions
- Follow PSR-4 autoloading for classes in `app/`; class names use `StudlyCaps`, methods and variables use `camelCase`.
- Keep Twig templates and view files lowercase, grouped by feature, for example `resources/views/themes/default/views/home/index.twig`.
- Use 4-space indentation in PHP and Twig files unless the surrounding file already uses a different convention.
- There is no project-wide formatter or linter configured in the repo, so keep changes consistent with nearby code.

## Testing Guidelines
- `phpunit.xml` defines `Unit` and `Feature` suites and expects test files named `*Test.php` under `tests/Unit` and `tests/Feature`.
- Add tests for controller, event, job, and middleware changes when behavior is affected.
- Keep assertions focused on observable behavior rather than framework internals.

## Commit & Pull Request Guidelines
- Recent history shows short, imperative commit subjects such as `Fix issue` and `Update composer`. Keep subjects concise and action-oriented.
- PRs should include a clear summary, the reason for the change, and any manual verification steps.
- Attach screenshots or request/response examples when UI, view, or API output changes.

## Security & Configuration Tips
- Never commit secrets or machine-specific values. Keep them in `.env` and derive the file from `.env.example`.
- Review changes to `config/`, `routes/`, and `public/` carefully, since they affect app startup and exposed endpoints.
