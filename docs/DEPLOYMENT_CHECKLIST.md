# Deployment Checklist — Aug 2026 (API / Contact Release)

Applies to the changeset that: (1) restored the `/api` prefix + `api` middleware group for all API routes, (2) wired the marketing-site contact form to the backend `POST /api/contact` (replacing Web3Forms), and (3) cleaned up stale Scribe artifacts.

## 1. Backend (`invent-mag`)

1. **Deploy code** (standard flow: pull, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache && php artisan route:cache`).
2. **No schema changes** in this release — the only new config is `config/app.php` → `contact_email` (env-driven).
3. **Set `CONTACT_EMAIL`** (optional). Defaults to `jefrydwijaya3@gmail.com` (founder).
   ```env
   CONTACT_EMAIL=jefrydwijaya3@gmail.com
   ```
4. **Verify mail transport** — contact submissions are now emailed in-app via SMTP (previously Web3Forms). Confirm in production `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=<smtp-host>
   MAIL_PORT=<port>
   MAIL_USERNAME=<user>
   MAIL_PASSWORD=<pass>
   MAIL_FROM_ADDRESS=no-reply@invent-mag.com
   MAIL_FROM_NAME=INVENT-MAG
   ```
   > **Note on defaults:** `config/mail.php` falls back to `noreply@invent-mag.web.id` if `MAIL_FROM_ADDRESS` is unset; the current `.env` overrides it with `no-reply@invent-mag.com`. Set it explicitly in production to avoid the fallback being used for outbound mail.
5. **Update the Midtrans Payment Notification URL** in the Midtrans dashboard (Settings → Configuration → Payment Notification URL):
   - **Old (root path — now 404s):** `https://api.invent-mag.web.id/webhooks/midtrans`
   - **New:** `https://api.invent-mag.web.id/api/webhooks/midtrans`
   - Midtrans keys unchanged: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`.
6. **Regenerate docs (optional, if routes change again):** `php artisan scribe:generate`.

## 2. Frontend (`invent-mag-fe`)

1. Deploy normally (Vercel). No new env vars — `PUBLIC_BACKEND_URL` already set:
   ```env
   PUBLIC_BACKEND_URL=https://api.invent-mag.web.id
   ```
2. Contact form on `/contact` and `/id/contact` now posts to `${PUBLIC_BACKEND_URL}/api/contact`. Web3Forms access key is no longer used.

## 3. Post-Deploy Smoke Test

| Check | Command / action | Expected |
|---|---|---|
| Tenant lookup | `curl -X POST https://api.invent-mag.web.id/api/lookup-tenant -H "Content-Type: application/json" -d '{"shopname":"demo"}'` | `200` or `404 {"message":"Shop not found."}` (not a 404 route error / 500) |
| Register endpoint | `curl -X POST https://api.invent-mag.web.id/api/register ...` | JSON response (not route-not-found) |
| CMS | `curl https://api.invent-mag.web.id/api/cms` | `200` JSON |
| Plans | `curl https://api.invent-mag.web.id/api/plans` | `200` JSON |
| Contact form | Submit `/contact` form | "Message Sent!" + email received at `CONTACT_EMAIL` |
| Midtrans webhook | Trigger a test transaction in Midtrans sandbox | Notification hits `POST /api/webhooks/midtrans` with `200` |
| API docs | `https://api.invent-mag.web.id/docs/` | 12 endpoints listed, including `POST /api/contact` |
| FE login/register | Attempt login + signup on marketing site | No `/api/register` / `/api/lookup-tenant` 404s |

## 4. Rollback Notes

- If `/api/*` calls fail, check `bootstrap/app.php` (the `using:` closure must wrap `routes/api.php` with `Route::middleware('api')->prefix('api')->group(...)`).
- The Midtrans webhook change is a **breaking URL change** — if it cannot be updated in the dashboard immediately, keep the old path working temporarily or pause notifications; the sandbox should be used to verify before switching production.
