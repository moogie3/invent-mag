# Invent-MAG — Owner Setup & Configuration Guide

> **Purpose:** A single, easy-to-follow guide for anyone who just got the project (bought it, received a copy, or is setting up a fresh deployment) — what to install, what to configure **first**, and how everything connects (domains, superadmin, landing-page CMS, taxes, accounting, billing, and payments).
>
> **Audience:** Non-developers and developers alike. Each step says *why* it matters.
>
> **Applies to:** Backend `invent-mag` (Laravel 11) + Frontend `invent-mag-fe` (Astro). Version 2.1.
>
> **Last updated:** August 2026

---

## Table of Contents

1. [How the system is organized (read this first)](#1-how-the-system-is-organized)
2. [Prerequisites](#2-prerequisites)
3. [Quick start](#3-quick-start)
4. [Environment configuration (`.env`)](#4-environment-configuration-env)
5. [Domains & subdomains setup](#5-domains--subdomains-setup)
6. [Run migrations & seeders](#6-run-migrations--seeders)
7. [Superadmin panel (first thing to configure)](#7-superadmin-panel)
8. [Landing-page CMS](#8-landing-page-cms)
9. [Frontend configuration & deployment](#9-frontend-configuration--deployment)
10. [Tenant (workspace) configuration](#10-tenant-workspace-configuration)
11. [Payments & billing configuration](#11-payments--billing-configuration)
12. [Production launch checklist](#12-production-launch-checklist)
13. [URL cheat-sheet (local vs production)](#13-url-cheat-sheet)
14. [Troubleshooting](#14-troubleshooting)
15. [Related documentation](#15-related-documentation)

---

## 1. How the system is organized

Invent-MAG is a **multi-tenant SaaS**: one backend serves many companies ("tenants"), and each company gets its own subdomain. There are four "surfaces" to keep in mind:

| Surface | What it is | Local URL (example) | Production URL (example) |
|---|---|---|---|
| **Marketing site (FE)** | The public landing page (Astro, built separately) | `http://127.0.0.1.nip.io` | `https://invent-mag.web.id` |
| **Backend + API** | The Laravel app. Serves the API (`/api/*`) and the Scribe docs (`/docs`) | `http://127.0.0.1.nip.io` | `https://api.invent-mag.web.id` |
| **Superadmin panel** | Platform operator's control room (tenants, plans, CMS, billing) | `http://super.127.0.0.1.nip.io` | `https://super.invent-mag.web.id` |
| **Tenant workspaces** | Each customer's app (login, inventory, sales, accounting…) | `http://demo-pro.127.0.0.1.nip.io` | `https://<tenant>.invent-mag.web.id` |

**Key mental model:**

- **The backend runs the superadmin panel and the API on the same codebase.** The subdomain decides which one you get (`super.` = superadmin, anything else = tenant or marketing).
- **The marketing site is a separate Astro project** (`invent-mag-fe`) that *reads* from the backend at build time. Editing the backend CMS is not enough — you must rebuild the frontend afterward.
- **Tenants are resolved by subdomain.** `demo-pro.…` → the "Demo Professional" tenant. No tenant DB per company — one central database, rows scoped per tenant.

---

## 2. Prerequisites

| For | Requires |
|---|---|
| **Local (XAMPP)** | PHP 8.2+, Composer, Node.js 18+/npm, MySQL, and optionally Redis |
| **Local (Docker)** | Docker + Docker Compose (see README "Docker Installation") |
| **Production** | PHP 8.2+ host, MySQL, Node (for the FE build), wildcard DNS, SSL |

> **⚠️ Most common first-run gotcha:** the `.env` defaults to **Redis** for cache & queue. If Redis is not installed/running, the site will error. For a plain XAMPP install use the database/file drivers — see [Section 4](#4-environment-configuration-env).

---

## 3. Quick start

### Option A — XAMPP (recommended for trying it locally)

```bash
# 1. Place the backend at C:\xampp\htdocs\invent-mag
#    and the frontend at C:\xampp\htdocs\invent-mag-fe

# 2. Backend dependencies + env
cd C:\xampp\htdocs\invent-mag
composer install
npm install
copy .env.example .env
php artisan key:generate

# 3. Frontend dependencies + env
cd C:\xampp\htdocs\invent-mag-fe
npm install
copy .env.example .env

# 4. Edit both .env files (Section 4), START MySQL + Apache in XAMPP
#    (and Redis if you keep the redis drivers)

# 5. Build the frontend once (marketing site)
npm run build

# 6. Point Apache's document root to C:\xampp\htdocs\invent-mag\public
#    and open http://127.0.0.1.nip.io
```

### Option B — Docker

```bash
git clone <your-repo> invent-mag
cd invent-mag
docker-compose up -d
docker-compose exec app php artisan migrate --seed
# Marketing site build: see Section 9
```

> After either option, **do not skip Section 6** (`migrate --seed`). It creates the superadmin account, the subscription plans, the taxes, and the demo workspaces.

---

## 4. Environment configuration (`.env`)

Copy `.env.example` to `.env`, then set the values below. These are the **minimum** keys that must be correct for the system to work as designed.

### 4.1 App identity & domains

| Key | Local (XAMPP) | Production (example) | Why it matters |
|---|---|---|---|
| `APP_NAME` | `INVENT-MAG` | `INVENT-MAG` | Shown in emails, titles |
| `APP_URL` | `http://127.0.0.1.nip.io` | `https://api.invent-mag.web.id` | Base URL for the backend; links generated from it |
| `APP_DOMAIN` | `127.0.0.1.nip.io` | `invent-mag.web.id` | **Builds tenant subdomains during seeding** (`demo-pro.<APP_DOMAIN>`) |
| `APP_URL_BASE` | `127.0.0.1.nip.io` | `invent-mag.web.id` | **Builds the superadmin domain** (`super.<APP_URL_BASE>`). Optional — if empty, falls back to the `APP_URL` host |
| `APP_FRONTEND_URL` | `http://127.0.0.1.nip.io` | `https://invent-mag.web.id` | Where the backend's root URL (`/`) redirects — visitors land on `{frontend_url}/login` (the marketing site's login) |
| `SESSION_DOMAIN` | `.127.0.0.1.nip.io` | `.invent-mag.web.id` | **Cookie scope.** The leading dot lets the cookie work across subdomains, keeping superadmin sessions out of tenant sessions. If empty, logins on subdomains misbehave |
| `APP_TIMEZONE` | `Asia/Jakarta` (recommended) | `Asia/Jakarta` | Date/time used for invoices & reports |
| `APP_LOCALE` | `en` | `en` | Default UI language (`id` = Indonesian) |

> **Why `127.0.0.1.nip.io` works locally:** `nip.io` is a free wildcard DNS service — `anything.127.0.0.1.nip.io` resolves to `127.0.0.1`. So `super.127.0.0.1.nip.io`, `demo-pro.127.0.0.1.nip.io`, etc. all point to your own machine with zero DNS setup.

### 4.2 Database

| Key | Local (XAMPP default) | Production |
|---|---|---|
| `DB_CONNECTION` | `mysql` | `mysql` |
| `DB_HOST` | `127.0.0.1` | your DB host |
| `DB_PORT` | `3306` | `3306` |
| `DB_DATABASE` | `invent_mag_be` | your database name |
| `DB_USERNAME` | `root` | your DB user |
| `DB_PASSWORD` | *(empty)* | your DB password |

> **First thing to check on a fresh machine:** is MySQL actually running? If the site shows a 500 "Service temporarily unavailable" on every page, MySQL is probably stopped (see [Troubleshooting](#14-troubleshooting)).

### 4.3 Cache & queue (critical gotcha)

| Key | Default in `.env.example` | Recommended for XAMPP (no Redis) |
|---|---|---|
| `CACHE_STORE` | `redis` | `database` (or `file`) |
| `QUEUE_CONNECTION` | `redis` | `database` (or `sync`) |

If you keep `redis`, a Redis server must be running or pages/jobs will fail. On XAMPP, use the **database** drivers — no extra software needed.

### 4.4 Mail (invoices & contact form)

`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. For local testing, Mailpit/MailHog (port 1025) or any SMTP works. Set `MAIL_FROM_ADDRESS` to a real "no-reply" address in production.

### 4.5 Payments (Midtrans)

| Key | Local/dev | Production |
|---|---|---|
| `MIDTRANS_IS_PRODUCTION` | `false` (sandbox) | `true` |
| `MIDTRANS_SERVER_KEY` | sandbox Server Key | production Server Key |
| `MIDTRANS_CLIENT_KEY` | sandbox Client Key | production Client Key |

Get keys at `https://dashboard.midtrans.com`. Without these, the plan checkout (payment popup) will not work. See [Section 11](#11-payments--billing-configuration).

### 4.6 Other keys that already exist / you may want

- `CONTACT_EMAIL` — where the marketing-site contact form emails go (default: `jefrydwijaya3@gmail.com`).
- `SCRIBE_AUTH_TOKEN` / `YOUR_API_TOKEN` — token Scribe uses as the bearer token in the generated API docs' example requests. It does **not** protect `/docs` (those are static files in `public/docs`).
- `LOG_LEVEL` — `debug` locally, `warning`/`error` in production.
- `APP_DEBUG` — `true` locally, **`false` in production**.

> **After editing `.env`:** run `php artisan config:clear` (or `config:cache` in production). Changing domains/keys while the app is cached can produce stale URLs.

---

## 5. Domains & subdomains setup

### 5.1 Local (XAMPP)

1. Make sure Apache is running and its document root points to `C:\xampp\htdocs\invent-mag\public`.
2. That's it — `127.0.0.1.nip.io` and any `*.127.0.0.1.nip.io` subdomain already resolve to `127.0.0.1`.
3. You can now open:
   - `http://super.127.0.0.1.nip.io` → superadmin
   - `http://demo-pro.127.0.0.1.nip.io` → a tenant workspace
   - `http://127.0.0.1.nip.io/docs` → API docs

### 5.2 Production

| DNS record | Type | Value | Purpose |
|---|---|---|---|
| `api.invent-mag.web.id` | A / CNAME | server IP / hostname | Backend + API + superadmin + tenants |
| `super.invent-mag.web.id` | A / CNAME | server IP | Superadmin panel |
| `*.invent-mag.web.id` | A | server IP | **Wildcard** — every tenant subdomain |
| `invent-mag.web.id` | A | Vercel / FE host | Marketing site |

Requirements:
- **Wildcard DNS** (`*.invent-mag.web.id`) so `any-tenant.invent-mag.web.id` reaches the backend.
- **Wildcard SSL** (e.g., Let's Encrypt DNS-01) so every subdomain has HTTPS.
- The FE (Astro) is usually hosted on **Vercel** at the root domain.

> Set `APP_URL_BASE` and `APP_DOMAIN` to the base domain (without `super.` / `www.`) in production, and `SESSION_DOMAIN` to `.invent-mag.web.id`.

---

## 6. Run migrations & seeders

```bash
cd invent-mag
php artisan migrate --seed
```

This one command creates everything you need to start:

| What | Details |
|---|---|
| **Superadmin account** | `superadmin@invent-mag.com` / `password` → logs into `super.<domain>/login` |
| **Plans** | Starter ($19), Professional ($59), Enterprise ($109) — all monthly, 30-day trial |
| **Roles & permissions** | `superuser`, `staff`, `pos` roles + all permissions |
| **6 workspaces** | `starter.`, `professional.`, `enterprise.`, `demo-starter.`, `demo-pro.`, `demo-enterprise.` |
| **Taxes (per workspace)** | PPN 11%, PPN 12%, PPh 23 (2%), PPh Final 0.5%, Non-PKP, PPN Inclusive |
| **Currencies, categories, units, products, customers, suppliers, warehouses, COA…** | Demo data so each workspace is usable immediately |

### Seeded logins (password is always `password`)

| URL | Email |
|---|---|
| `super.<domain>` | `superadmin@invent-mag.com` |
| `demo-starter.<domain>` | `starter@demo.com` |
| `demo-pro.<domain>` | `pro@demo.com` |
| `demo-enterprise.<domain>` | `enterprise@demo.com` |
| `starter.<domain>` | `admin-starter-tenant@gmail.com` |
| `professional.<domain>` | `admin-professional-tenant@gmail.com` |
| `enterprise.<domain>` | `admin-enterprise-tenant@gmail.com` |

> **Never run `migrate:fresh` on a live database** — it wipes all tenants. Add new migrations normally (`php artisan migrate`).

---

## 7. Superadmin panel

Open `super.<domain>` and log in with the superadmin credentials. This is the operator's control room.

### 7.1 Panel tour (sidebar)

| Page | URL (on `super.<domain>`) | What you do there |
|---|---|---|
| Dashboard | `/` | Platform overview |
| Tenants | `/tenants` | View workspaces, activate/deactivate, **edit a tenant's subdomain**, impersonate a tenant |
| Plans | `/plans` | **Edit plan pricing, limits, features** (this is the pricing editor) |
| Subscriptions | `/subscriptions` | Terminate / reactivate a tenant's subscription |
| Features | `/features` | Toggle feature slugs per plan tier |
| Billing / Payment Overview / Transactions / Pending Payments / Invoices | `/billing`, `/payment-overview`, `/transactions`, `/pending-payments`, `/invoices` | Revenue, order status, void orders, approve/reject payments |
| **Settings** | `/settings` | **Application settings (.env editor)** — edit URLs, mail, debug mode |
| Settings → Platform Currency | `/settings/platform-currency` | Symbol/separator for superadmin money displays |
| Settings → **Billing Rate** | `/settings/billing-rate` | **USD→IDR rate** used when charging tenants (see Section 11) |
| Settings → Preferences | `/settings/preferences` | Your theme, notifications |
| Backups | `/backups` | Create/restore/download DB backups |
| Audit Log | `/audit-log` | Platform-level activity log |
| Permissions | `/permissions` | View-only list of tenant roles/permissions |
| **CMS** | `/cms` | Edit the **marketing landing page** (see Section 8) |

### 7.2 First-time superadmin to-dos (in order)

1. **Settings → Application Settings** — verify `APP_URL`, `APP_DOMAIN`, `APP_URL_BASE`, `SESSION_DOMAIN`, and `MAIL_*`. (This is the .env editor — be careful; restart/clear config after saving.)
2. **Plans** — confirm the 3 plans exist and the prices/limits are what you want to sell.
3. **Settings → Billing Rate** — set today's USD→IDR rate (default fallback is 16,000).
4. **CMS** — fill in the landing-page content (Section 8).
5. **Tenants** — verify the demo workspaces exist and their subdomains are correct.

---

## 8. Landing-page CMS

The marketing page is **managed by the superadmin** and **served to the frontend at build time**.

### How it works

1. Superadmin edits content at `super.<domain>/cms` (hero, features, pricing badges, CTA, contact email).
2. Saving writes `storage/app/cms.json` and the API exposes it at `GET /api/cms` (public, includes the live `billing_rate`).
3. The Astro frontend fetches `/api/cms` **while building** and bakes the text into static pages.

### To edit the landing page

```
Local:   http://super.127.0.0.1.nip.io/cms
Prod:    https://super.invent-mag.web.id/cms
```

After saving content you must **rebuild the frontend** for it to appear:

```bash
cd invent-mag-fe
npm run build        # local preview
# OR push to Vercel (auto-deploy) in production
```

> Full walkthrough, field-by-field reference, and FE setup live in `docs/LANDING_PAGE_CMS.md`.

---

## 9. Frontend configuration & deployment

The `invent-mag-fe` project is a separate Astro static site (usually deployed on **Vercel**). Its `.env`:

| Key | Local | Production |
|---|---|---|
| `PUBLIC_BACKEND_URL` | `http://127.0.0.1.nip.io` | `https://api.invent-mag.web.id` |
| `PUBLIC_DEMO_STARTER_URL` | `http://demo-starter.127.0.0.1.nip.io/admin/login` | `https://demo-starter.invent-mag.web.id/admin/login` |
| `PUBLIC_DEMO_PRO_URL` | `http://demo-pro.127.0.0.1.nip.io/admin/login` | `https://demo-pro.invent-mag.web.id/admin/login` |
| `PUBLIC_USD_TO_IDR` | `16000` | `16000` (fallback only — the backend `/api/cms` `billing_rate` wins) |

Build & preview:

```bash
cd invent-mag-fe
npm install
npm run build      # generates the static site
npm run preview    # serve it locally
```

**Important:** because the site is static, any backend change the frontend reads (CMS content, plan prices, billing rate) requires a **rebuild/deploy** of the FE. This is expected.

---

## 10. Tenant (workspace) configuration

Each company uses its own subdomain, e.g. `http://demo-pro.<domain>`. Log in with that workspace's email → **Settings** (gear icon) has everything below. *(Most items require the `superuser` role in that workspace.)*

### 10.1 Tax configuration (important for Indonesian users)

- **Settings → System Settings** → **Tax Region** card: choose the tax jurisdiction.
- **When jurisdiction is set to Indonesia, the system auto-creates the standard tax records** (`PPN 11%` and `PPh 23 (2%)`) — no manual setup needed for the common case.
- **Settings → Tax** (`/admin/settings/tax`): full CRUD for tax rates — name, rate (%), type (PPN / PPh / general / no tax), inclusive vs exclusive, and which is the **default** for new documents.
- Taxes apply to Sales Orders, POS, Purchases, and Quotations. The docs `TAXATION_SYSTEM.md` and `TAXATION_IMPROVEMENT.md` explain the model in depth.

### 10.2 Currency (tenant-level)

**Settings → Currency** (`/admin/settings/currency`) — symbol, thousands/decimal separators, decimal places, and currency code/locale **for that workspace only**. Note: this is display formatting only — there is **no** exchange-rate field or multi-currency conversion engine (per the `CurrencySetting` model). This does **not** affect the superadmin's "Platform Currency".

### 10.3 Accounting (Professional+)

**Settings → Accounting** (`/admin/settings/accounting`):
1. **Reset Chart of Accounts** to the default COA.
2. **Configure Accounting Integration** (cash, AP, inventory, AR, COGS, revenue accounts).
3. **Set Opening Balances** (critical — see `docs/ACCOUNTING_SETUP_GUIDE.md`).

### 10.4 Plan & payment

**Settings → Plan** (`/admin/settings/plan`) — view current plan, usage vs limits, and **Upgrade** → Midtrans checkout. After payment, the plan upgrades immediately (webhook as backup).

### 10.5 Profile & API token

**Settings → Profile** (`/admin/settings/profile`) — name, password, avatar, and (on `api_access` plans) **Generate API token** for the REST API.

---

## 11. Payments & billing configuration

### 11.1 Midtrans (the payment gateway)

1. Create an account at `https://dashboard.midtrans.com`.
2. Put the keys in `.env` (Section 4.5): sandbox keys for dev, production keys when live.
3. In the Midtrans dashboard, set the **Payment Notification URL** to:
   - `https://api.invent-mag.web.id/api/webhooks/midtrans` (primary)
   - `https://api.invent-mag.web.id/api/webhook` (fallback alias)
4. Test with sandbox → switch `MIDTRANS_IS_PRODUCTION=true` → test a real payment.

### 11.2 Billing rate (USD → IDR)

Tenants see **USD** prices on the upgrade page plus the **IDR equivalent** ("≈ Rp …"). The IDR amount = `USD price × billing_rate`, then `+ 12% tax`.

- Set/update the rate at **Superadmin → Settings → Billing Rate** (`super.<domain>/settings/billing-rate`).
- If no rate is saved yet, the fallback in `config/billing.php` is used (`fallback_rate` = 16000, `tax_rate` = 0.12).
- The rate is also exposed at `GET /api/cms` (`billing_rate`), so after changing it, **rebuild the FE** so the pricing page shows the new IDR figures.

### 11.3 Plan pricing

Edit prices/limits/features at **Superadmin → Plans** (`super.<domain>/plans`). After changing prices, **rebuild the FE** (the public `/pricing` page reads the plans at build time via `GET /api/plans`).

### 11.4 Platform currency (superadmin display only)

**Superadmin → Settings → Platform Currency** controls symbol/separators for superadmin money displays. It does not affect tenant workspaces.

---

## 12. Production launch checklist

Before going live, go through this list:

1. **Domains** — wildcard DNS (`*.yourdomain`), `api.`/`super.` records, wildcard SSL (Section 5.2).
2. **`.env`** — `APP_DEBUG=false`, production `APP_URL`/`APP_DOMAIN`/`APP_URL_BASE`, `SESSION_DOMAIN=.yourdomain`, real `MAIL_*`, production Midtrans keys, `CONTACT_EMAIL`.
3. **Caches & config** — `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`; set `CACHE_STORE`/`QUEUE_CONNECTION` appropriately.
4. **Queue worker** (if `QUEUE_CONNECTION` is `database`/`redis`) — run a worker for queued emails (invoice, contact form).
5. **Scheduler / cron** — add the scheduler for any scheduled tasks (backups, notifications).
6. **Backups** — configure Spatie backups; do one now.
7. **Migrations** — `php artisan migrate` (apply all pending migrations; run `--seed` only on a fresh DB).
8. **Frontend** — deploy `invent-mag-fe` with the production `.env`; verify `/pricing`, `/login` (lookup-tenant), and the contact form against the live API.
9. **Smoke test** — superadmin login, one tenant login, create a sale with PPN, run a trial balance, test a sandbox payment.
10. **Security** — confirm `APP_DEBUG=false`, strong superadmin password (change from `password`!), and review the Audit Log after a few days.

---

## 13. URL cheat-sheet

| What | Local | Production (example) |
|---|---|---|
| Marketing site | `http://127.0.0.1.nip.io` | `https://invent-mag.web.id` |
| API docs (Scribe) | `http://127.0.0.1.nip.io/docs` | `https://api.invent-mag.web.id/docs` |
| Superadmin login | `http://super.127.0.0.1.nip.io/login` | `https://super.invent-mag.web.id/login` |
| Superadmin CMS | `http://super.127.0.0.1.nip.io/cms` | `https://super.invent-mag.web.id/cms` |
| Superadmin Plans | `http://super.127.0.0.1.nip.io/plans` | `https://super.invent-mag.web.id/plans` |
| Superadmin Billing Rate | `http://super.127.0.0.1.nip.io/settings/billing-rate` | `https://super.invent-mag.web.id/settings/billing-rate` |
| Tenant login | `http://demo-pro.127.0.0.1.nip.io/admin/login` | `https://<tenant>.invent-mag.web.id/admin/login` |
| Tenant Settings | `http://demo-pro.127.0.0.1.nip.io/admin/settings` | `https://<tenant>.invent-mag.web.id/admin/settings` |
| Tenant Tax | `http://demo-pro.127.0.0.1.nip.io/admin/settings/tax` | `https://<tenant>.invent-mag.web.id/admin/settings/tax` |
| Tenant Currency | `http://demo-pro.127.0.0.1.nip.io/admin/settings/currency` | `https://<tenant>.invent-mag.web.id/admin/settings/currency` |
| Tenant Accounting | `http://demo-pro.127.0.0.1.nip.io/admin/settings/accounting` | `https://<tenant>.invent-mag.web.id/admin/settings/accounting` |
| Tenant Plan/Upgrade | `http://demo-pro.127.0.0.1.nip.io/admin/settings/plan/upgrade` | `https://<tenant>.invent-mag.web.id/admin/settings/plan/upgrade` |

---

## 14. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| **Every page → 500 "Service temporarily unavailable"** | **MySQL not running** (or DB creds wrong) | Start MySQL in XAMPP / check `DB_*` in `.env`; see `storage/logs/laravel.log` |
| **Pages error on fresh install** | Redis configured but not running | Set `CACHE_STORE=database` and `QUEUE_CONNECTION=database` in `.env` |
| **Login page loops / cookies shared between workspaces** | `SESSION_DOMAIN` empty or wrong | Set `SESSION_DOMAIN=.127.0.0.1.nip.io` (local) or `.yourdomain` (prod) |
| **`super.…` page not found / 404** | `APP_URL_BASE` not set or DNS missing | Set `APP_URL_BASE`; add the `super.` DNS record |
| **Landing page shows old CMS/pricing** | FE is static and not rebuilt | `cd invent-mag-fe && npm run build` (or redeploy on Vercel) |
| **Checkout popup fails / payment doesn't open** | Midtrans keys missing/wrong | Check `MIDTRANS_*` in `.env`; sandbox vs production |
| **419 Page Expired** | Session/CSRF expiry or session driver issue | Refresh the page; check `SESSION_DRIVER` and DB sessions table |
| **"Workspace not found"** | Subdomain not seeded or not DNS | Run seeders (Section 6) / fix DNS |
| **Superadmin edit page 500s** | MySQL down **or** pending migrations | Start MySQL; run `php artisan migrate` |
| **URLs look stale after changing `.env`** | Cached config | `php artisan config:clear` (or `config:cache` after) |

---

## 15. Related documentation

| Guide | What it covers |
|---|---|
| `docs/LANDING_PAGE_CMS.md` | Full landing-page CMS walkthrough + FE setup |
| `docs/ACCOUNTING_SETUP_GUIDE.md` | Step-by-step accounting setup (COA, integration, opening balances) |
| `docs/TAXATION_SYSTEM.md` | The Indonesian tax model (PPN / PPh) |
| `docs/TAXATION_IMPROVEMENT.md` | Tax CRUD design & calculation semantics |
| `docs/DEPLOYMENT_CHECKLIST.md` | Backend + FE deployment steps |
| `docs/TECHNICAL_SPECIFICATION.md` | Architecture, security, API reference |
| `docs/STRUCTURE.md` | Codebase layout |
| `README.md` | Feature list, installation, Docker |
