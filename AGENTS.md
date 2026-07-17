# Repository Guidelines

## Project Structure & Module Organization

This LMS uses Laravel 13 with Inertia, React 19, and TypeScript. Backend code lives under `app/`, routes in `routes/`, and migrations, factories, and seeders in `database/`. React pages, components, layouts, hooks, and types are under `resources/js/`; Tailwind styles begin in `resources/css/app.css`. Public static files belong in `public/`. Place tests in `tests/Feature` or `tests/Unit`.

## Build, Test, and Development Commands

- `composer run setup` installs dependencies, prepares `.env`, migrates, and builds assets.
- `composer run dev` starts Laravel, the queue listener, and Vite together.
- `npm run build` creates the production frontend bundle; `npm run build:ssr` also builds SSR assets.
- `composer test` runs Pint checks, PHPStan level 7, and the Pest test suite.
- `composer ci:check` additionally runs frontend linting, formatting, and TypeScript checks.
- `npm run lint` and `npm run format` automatically fix frontend issues.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, four-space indentation (two for YAML), and a final newline. PHP follows Laravel Pint and PSR-4 namespaces such as `App\Http\Controllers`. Use PascalCase for classes and React components, camelCase for functions and variables, and kebab-case for React filenames (for example, `learning-materials/create.tsx`). Keep imports sorted and use top-level `import type` declarations.

## Testing Guidelines

Tests use Pest 4. Name files `*Test.php`; favor Feature tests for routes and database behavior, and Unit tests for isolated logic. Feature tests use `RefreshDatabase`; factories should supply records. Tests run against in-memory SQLite. No coverage threshold is enforced, but behavior changes should include regression tests. Run one test with `php artisan test --filter=UserManagementTest`.

## Commit & Pull Request Guidelines

Recent commits use short, imperative summaries such as `Add direct S3 upload flow for learning materials`. Keep commits focused. Pull requests should explain the change and verification, link relevant issues, call out migrations or environment changes, and include screenshots for UI updates. Ensure `composer ci:check` passes before review.

## Security & Configuration

Copy `.env.example` locally and never commit `.env`, credentials, or S3 keys. Add new configuration through `config/` and document required environment variables in `.env.example`. Validate uploads and authorization on the server, even when the UI already restricts them.
