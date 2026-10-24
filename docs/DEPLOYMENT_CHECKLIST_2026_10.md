# Deployment Checklist — Oct 2026 (Shopee Categories / Low-Stock Fix / Datasheet / Apache-2.0)

Applies to the changeset that: (1) makes **Apache-2.0** the single consistent license (README, `composer.json`, `package.json`), (2) auto-files **Shopee imports** into the shop's 35-category SKU taxonomy instead of the shared "Shopee Import" bucket, (3) fixes **low-stock** counting for NULL thresholds (COALESCE everywhere + import default of 10 + backfill migration), and (4) adds a **Datasheet** dropdown item (alldatasheet.com) on semiconductor product rows.

> Previous release checklist: [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) (Aug 2026 — API/contact release).

## 1. Backend (`invent-mag`)

1. **Deploy code** (standard flow: pull, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache && php artisan route:cache`). The tag-triggered workflow (`.github/workflows/deploy.yml`) now also runs `php artisan categories:backfill-shopee` right after `migrate --force`.
2. **Schema changes:** one data migration — `2026_10_24_000001_backfill_low_stock_threshold_to_default` sets `products.low_stock_threshold = 10` where it is NULL (idempotent, runs automatically with `migrate --force`).
3. **Legacy category re-file:** `php artisan categories:backfill-shopee` moves products whose `category_id` points at a category named "Shopee Import" into the resolver's taxonomy.
   - Idempotent — re-runs report `Nothing to do — no products found in "Shopee Import" categories.` once done, so running it on every deploy is safe.
   - Preview first with `--dry-run`; scope to one tenant with `--tenant=<id>`.
   - The deploy workflow runs it automatically after migrations; to run manually on the server:
     ```bash
     docker-compose exec -T app php artisan categories:backfill-shopee --dry-run
     docker-compose exec -T app php artisan categories:backfill-shopee
     ```
4. **No new env vars.** `config/shopee_categories.php` is a normal config file — picked up by `php artisan config:cache` (already in the deploy script).
5. **Frontend build** happens in CI (`npm run build` in `ci.yml`) and is baked into the Docker image — no Vercel step needed for this repo.
6. **Optional:** regenerate Scribe docs (`php artisan scribe:generate`) — no API routes changed in this release, so not required.

## 2. CI (`.github/workflows/ci.yml`)

- Runs on push/PR to `main`/`develop`: `npm run build` → **`php artisan test`** (1246 tests) → **`npx vitest run`** (209 tests). The vitest step was added in this release — the JS suite (incl. the new datasheet tests) previously never ran in CI.
- No CI config changes are needed for the migration or backfill (tests use sqlite `:memory:`).

## 3. Post-Deploy Smoke Test

| Check | Command / action | Expected |
|---|---|---|
| Threshold backfill | `docker-compose exec -T app tinker` → `App\Models\Product::whereNull('low_stock_threshold')->count()` | `0` |
| Category re-file | `docker-compose exec -T app php artisan categories:backfill-shopee` | `Nothing to do — no products found in "Shopee Import" categories.` |
| Residual bucket | Products page → filter category "Shopee Import" | No products (category itself may still exist, unused) |
| New import | Import a sample Shopee XLSX | Products land in guide categories (IC/TRS/CAP/…), each with `low_stock_threshold = 10` |
| Low stock | Notifications modal + header badge with a product at qty 10 vs 11 | Only qty ≤ threshold flagged; NULL-threshold products no longer flagged as everything |
| Datasheet | ⋯ menu on an IC / transistor / diode product row | "Datasheet" item opens `alldatasheet.com/view.jsp?Searchword=...` in a new tab; a resistor/capacitor row has no such item |
| License | Search "MIT License" in `README.md` | No matches (Apache-2.0 throughout) |

## 4. Rollback Notes

- Migration is additive (fills NULL → 10); rolling back code without the migration is safe — old code ignores `low_stock_threshold` only where it already did, and COALESCE handles NULLs.
- To undo the category re-file (not normally needed), reassign the affected products back to a legacy category; the command itself has no destructive side effects and `--dry-run` shows counts before writing.
- Datasheet is UI-only (one dropdown link); rollback is just reverting the blade/JS files.
