# CoalitionTech Product Inventory

[![Tests](https://github.com/LukasLeona/CoalitionTech/actions/workflows/tests.yml/badge.svg)](https://github.com/LukasLeona/CoalitionTech/actions/workflows/tests.yml)

A focused Laravel skills-test submission for entering inventory products and reviewing their value in real time. The interface uses Bootstrap 5.3 and progressively enhanced JavaScript: create and edit requests run through AJAX, while Laravel remains the source of validation, persistence, totals, and response formatting.

**Public repository:** [github.com/LukasLeona/CoalitionTech](https://github.com/LukasLeona/CoalitionTech)

## Evaluation checklist

- Laravel 13 application running on PHP 8.3
- Routes, controller, Eloquent model, form requests, API resource, migration, and seeder
- Product name, quantity in stock, and price per item inputs
- Server-calculated row totals (`quantity_in_stock × price_per_item`) and grand total
- Newest-first ordering by submission time
- AJAX creation and inline editing without a page reload
- SQLite persistence plus a synchronized, valid JSON snapshot
- Responsive Bootstrap 5.3 UI with local production assets
- Feature and unit tests, Laravel Pint, and dependency auditing in CI

## Fast evaluator setup

Requirements: PHP 8.3+ with SQLite/PDO SQLite, Composer 2, and the PHP extensions Laravel normally requires (`ctype`, `fileinfo`, `mbstring`, `openssl`, `pdo`, and `tokenizer`). No Node.js toolchain is required.

From the extracted project or a fresh clone, run the included setup script:

```bash
composer run setup
```

It installs PHP dependencies, copies `.env.example` when `.env` is absent, creates `database/database.sqlite` when needed, generates the application key, and runs the migrations. No database server, connection edit, or frontend build step is required.

Then start the development server:

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

For a conventional Apache or Nginx deployment, extract the submission and point the site document root to `public/`. The submission ZIP is assembled for direct extraction and includes its ready-to-run environment, SQLite database, installed PHP dependencies, and local browser assets. No hard-coded machine paths, credentials, database server, or source-code edits are required. If a transfer process strips generated dependencies, `composer run setup` restores them.

To rebuild a clean sample dataset at any time:

```bash
php artisan migrate:fresh --seed
```

## How it works

The page first renders a complete table on the server, then the browser submits create and edit forms with `fetch`. Every successful mutation returns the same canonical snapshot shape:

```json
{
  "data": [],
  "meta": {
    "grand_total": "0.00",
    "record_count": 0,
    "total_units": 0
  },
  "message": "Product saved successfully."
}
```

The browser redraws the rows, summary values, and grand total from that response. Validation failures use Laravel's standard HTTP 422 response and are presented next to the relevant fields. Values are formatted for display, but multiplication and aggregation remain server-side so the client cannot supply authoritative totals.

### Routes

| Method | URI | Name | Responsibility |
| --- | --- | --- | --- |
| `GET` | `/` | `products.index` | Render the dashboard |
| `GET` | `/products` | `products.list` | Return the current JSON snapshot |
| `POST` | `/products` | `products.store` | Validate and create a product |
| `PATCH` | `/products/{product}` | `products.update` | Validate and update a product |

### Application layers

- `ProductController` coordinates the page and AJAX endpoints.
- `StoreProductRequest` and `UpdateProductRequest` own input authorization, normalization, and validation.
- `Product` provides typed casts and the calculated total-value representation.
- `ProductResource` gives every frontend response a stable, presentation-ready shape.
- `ProductJsonSyncService` writes a complete snapshot after each successful database mutation.
- The migration defines the inventory schema; the seeder supplies deterministic evaluator data.
- The Blade view, Bootstrap styles, and JavaScript module provide the responsive interface and inline editing experience.

SQLite is the transactional system of record. The secondary JSON document is generated from committed database state and written through Laravel's local filesystem disk to `storage/app/private/products.json`. It is always encoded as a complete JSON document rather than appended fragments, so it remains valid and portable.

## Tests and quality checks

Run the automated suite:

```bash
composer test
```

Run the same additional checks used in GitHub Actions:

```bash
vendor/bin/pint --test
composer audit --no-interaction
```

The feature coverage exercises page rendering, newest-first listing, valid creation, validation failures, editing, calculated values, and aggregate metadata. Unit coverage verifies JSON synchronization and valid snapshot content. Tests use an isolated database and do not depend on the evaluator's local inventory data.

The workflow in `.github/workflows/tests.yml` installs dependencies on PHP 8.3, prepares SQLite, runs all migrations and tests, checks Laravel Pint formatting, and audits Composer dependencies.

## Submission timing evidence

The public Git history is intentionally unsquashed so the assessment window is independently visible. Work began with:

- [`ffb8db4939ebdfd136c7ac3e6791e7aa3e47a674` — “Laravel Follow-up Skills Test: Start skills test”](https://github.com/LukasLeona/CoalitionTech/commit/ffb8db4939ebdfd136c7ac3e6791e7aa3e47a674)
- Commit timestamp: **October 20, 2026 at 4:55:35 PM (Asia/Manila, UTC+08:00)**

GitHub's public commit and Actions timestamps provide the external record of the implementation window.

## Technology

- PHP 8.3
- Laravel 13
- SQLite
- Bootstrap 5.3
- Vanilla JavaScript Fetch API
- PHPUnit 12
