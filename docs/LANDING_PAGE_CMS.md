# Landing Page CMS — How It Works

## Overview

The landing page at [invent-mag.web.id](https://invent-mag.web.id) is built with **Astro** (static site generator) and deployed on **Vercel**. Content is managed through a CMS in the Super Admin panel (`super.{domain}/cms`), which saves to `storage/app/cms.json`. The frontend fetches this content at build time and bakes it into static HTML.

### Architecture

```
Super Admin CMS → cms.json → GET /cms API → Astro build → Static HTML (Vercel)
```

**Single source of truth:** `storage/app/cms.json` stores all editable content for the landing page — hero, features, pricing, CTA, and footer.

---

## Local Development

### Prerequisites

- **XAMPP** (or equivalent) running Apache with PHP
- **Node.js** installed (for Astro)

### Step-by-step

1. **Start the Laravel backend:**
   - Ensure Apache is running in XAMPP
   - The site should be accessible at `http://127.0.0.1.nip.io` (or your configured APP_URL)

2. **Start the Astro frontend dev server:**
   ```bash
   cd invent-mag-fe
   npm run dev
   ```
   This starts the dev server at `http://127.0.0.1:4321`

3. **Edit content:**
   - Go to `http://super.127.0.0.1.nip.io/cms`
   - Make your changes and click **Save Changes**
   - Refresh the landing page — changes appear immediately in dev mode

4. **Preview:** Visit `http://127.0.0.1:4321` to see your changes

---

## Production Deployment

### How Changes Go Live

When you save CMS changes and push to Git, Vercel automatically rebuilds and deploys the frontend with the updated content.

### Step-by-step

1. **Edit content** in the Super Admin CMS (`super.{domain}/cms`)
2. **Commit the changes** to Git — the `storage/app/cms.json` file is what changes
3. **Push to your repository** — Vercel detects the push and triggers a rebuild
4. **Wait ~1-2 minutes** — Vercel rebuilds the Astro frontend with new content
5. **Verify** on [invent-mag.web.id](https://invent-mag.web.id)

> **Note:** CMS changes only take effect after a new build. Unlike a traditional CMS with a database, this is a **build-time CMS** — content is baked into static HTML at build time.

---

## Environment Configuration

The Astro frontend needs the `PUBLIC_BACKEND_URL` environment variable to know where to fetch CMS data from.

| Environment | `PUBLIC_BACKEND_URL` | Where to Configure |
|---|---|---|
| **Local** | `http://127.0.0.1.nip.io` | `invent-mag-fe/.env` |
| **Production** | `https://api.invent-mag.web.id` | Vercel Dashboard → Settings → Environment Variables |

### Setting Up for Local Development

Create a `.env` file in `invent-mag-fe/`:

```env
PUBLIC_BACKEND_URL=http://127.0.0.1.nip.io
PUBLIC_DEMO_STARTER_URL=https://demo-starter.invent-mag.web.id/admin/login
PUBLIC_DEMO_PRO_URL=https://demo-pro.invent-mag.web.id/admin/login
```

> **Important:** The `.env` file is gitignored — it will NOT be committed to the repository.

### Setting Up for Production (Vercel)

1. Go to **Vercel Dashboard → Your Project → Settings → Environment Variables**
2. Add the following:

| Key | Value | Environment |
|---|---|---|
| `PUBLIC_BACKEND_URL` | `https://api.invent-mag.web.id` | Production, Preview, Development |

3. Trigger a redeploy for the changes to take effect

---

## API Endpoint

The CMS content is served via a public API endpoint (no authentication required):

```
GET /cms
```

**Response:** JSON object with all CMS fields (see below)

**CORS:** Already configured to allow all `*.invent-mag.web.id`, `*.vercel.app`, and `*.nip.io` origins.

**Caching:** Response includes `Cache-Control: public, max-age=3600` (1 hour browser cache).

---

## CMS Fields Reference

### Hero Section

| Field | Description | Example |
|---|---|---|
| `hero_title` | Main headline | `The All-In-One ERP to Streamline Your Business` |
| `hero_subtitle` | Subtitle below headline | `From inventory and sales to a full accounting suite...` |
| `hero_cta_text` | Primary button text | `Start 30-Day Free Trial` |
| `hero_cta_url` | Primary button link | `/signup` |
| `hero_cta_demo_text` | Secondary button text | `Try Live Demo` |
| `hero_cta_demo_url` | Secondary button link | `https://demo-pro.invent-mag.web.id/admin/login` |

### Features Section

| Field | Description |
|---|---|
| `features_title` | Section heading |
| `features_subtitle` | Sub-heading |
| `features_description` | Body text |
| `features_enterprise_heading` | Enterprise subsection heading |
| `feature_card_{1-11}_title` | Feature card title |
| `feature_card_{1-11}_desc` | Feature card description |

**Feature cards:**
1. Inventory & Product Management
2. Sales, CRM & Quotations
3. Purchase & SRM
4. Financial & Accounting Suite
5. HR & Payroll
6. Multi-Currency Support
7. Returns Management
8. User & Role Management
9. Sales Forecasting AI
10. RESTful API Access
11. Audit Trail & Compliance

### Pricing Section

| Field | Description | Example |
|---|---|---|
| `pricing_badge` | Badge above title | `Simple, transparent pricing` |
| `pricing_title` | Section heading | `One platform, every tool your business needs` |
| `pricing_subtitle` | Description (supports HTML) | `From inventory tracking to full double-entry accounting &mdash; pick the plan...` |
| `pricing_no_credit_badge` | Green badge text | `No credit card required` |
| `pricing_trial_badge` | Green badge text | `30-day free trial` |
| `pricing_cancel_badge` | Green badge text | `Cancel anytime` |
| `pricing_most_popular_badge` | Professional plan badge | `most popular` |
| `pricing_trial_included` | Trial text on Professional plan | `30 days free trial included` |
| `pricing_enterprise_support` | Enterprise plan badge | `Enterprise support & SLA` |

### CTA Section

| Field | Description |
|---|---|
| `cta_title` | Section heading |
| `cta_description` | Body text |

### Footer & Contact

| Field | Description |
|---|---|
| `contact_email` | Email address in footer |
| `footer_text` | Copyright / footer text |

---

## How the Frontend Uses CMS Data

### Build-Time Fetching (SSG)

During `astro build`, each page calls `getCmsContent()` which fetches from the Laravel API:

```
Frontend build → GET {PUBLIC_BACKEND_URL}/cms → CMS data → Static HTML
```

This means:
- **SEO-friendly:** Content is in the HTML on first paint
- **Fast:** No client-side JS needed to load content
- **Reliable:** No risk of content failing to load (API down, CORS, ad blockers)

### Data Flow

1. **Astro frontmatter** (server-side): Fetches CMS data, passes as props to components
2. **Components** (build-time): Render CMS values directly into HTML elements
3. **i18n translations** (client-side): CMS data is injected via `window.__CMS_DATA__` for the English translations. Indonesian translations remain hardcoded.

### Key Files

| File | Purpose |
|---|---|
| `invent-mag-fe/src/lib/cms.ts` | CMS data fetcher with typed interface and defaults |
| `invent-mag-fe/src/layouts/BaseLayout.astro` | Passes CMS data to i18n translations |
| `invent-mag-fe/src/components/Hero.astro` | Hero section (accepts CMS props) |
| `invent-mag-fe/src/components/Features.astro` | Features section (accepts CMS data) |
| `invent-mag-fe/src/components/Pricing.astro` | Pricing section (accepts CMS data) |
| `invent-mag-fe/src/components/Cta.astro` | CTA section (accepts CMS props) |
| `invent-mag-fe/src/components/Footer.astro` | Footer (accepts CMS props) |
| `invent-mag-fe/src/pages/index.astro` | EN homepage (fetches CMS, passes to components) |
| `invent-mag-fe/src/pages/pricing.astro` | EN pricing page |
| `app/Http/Controllers/Api/CmsController.php` | Public API endpoint serving CMS data |
| `storage/app/cms.json` | CMS data store |

---

## Troubleshooting

| Problem | Solution |
|---|---|
| **Changes not showing (production)** | Ensure changes are committed and pushed to Git. Vercel rebuilds automatically. |
| **Wrong content showing** | Check `PUBLIC_BACKEND_URL` in `.env` (local) or Vercel settings (production). |
| **404 from API** | Ensure the Laravel backend is running and accessible at the configured URL. |
| **Stale content in dev** | Restart the Astro dev server: `npm run dev` |
| **Build fails** | Check that `PUBLIC_BACKEND_URL` is set. The fetcher falls back to defaults if unreachable. |
| **CORS errors** | Verify the frontend domain is in `config/cors.php` allowed origins. |
