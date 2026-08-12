# Invent-MAG: Indonesian Taxation System

> **Last updated:** 2026-08-05
> **Status:** Universal tax system + Indonesian compliance (PPN Keluaran/Masukan, e-Faktur, PPh 23, PPh Final 0.5%). This document is the single source of truth for *why* the tax system is shaped the way it is and how it maps to Indonesian law.

---

## Table of Contents

1. [Purpose](#1-purpose)
2. [The 2026 Indonesian Tax Landscape](#2-the-2026-indonesian-tax-landscape)
3. [Seed Set Rationale](#3-seed-set-rationale)
4. [The Universal Tax Model](#4-the-universal-tax-model)
5. [How Each Tax Flows Through the System](#5-how-each-tax-flows-through-the-system)
6. [Journal Entry Accounting](#6-journal-entry-accounting)
7. [Calculation Rules](#7-calculation-rules)
8. [Tax Groups & the Internationalization Phase](#8-tax-groups--the-internationalization-phase)
9. [Known Limitations (Audit Caveats)](#9-known-limitations-audit-caveats)
10. [Reference: Data Model](#10-reference-data-model)

---

## 1. Purpose

Invent-MAG is a multi-tenant SaaS ERP targeting **Indonesian SMEs**. Indonesian tax compliance is a legal requirement for most of our users, and the tax system must therefore reflect real DJP rules — not generic "VAT-style" assumptions. This document:

- Maps each seeded tax record to the regulation that justifies it.
- Explains the universal tax model shared by all transaction types.
- Documents the accounting treatment (journal entries) of every tax.
- Records known limitations and audit findings.

Related docs: `docs/PPN_EVOLUTION.md` (chronological feature history), `docs/TECHNICAL_SPECIFICATION.md` (field-level schema).

---

## 2. The 2026 Indonesian Tax Landscape

Indonesia's value-added and income taxes were substantially reformed between 2022 and 2026. The seed set below is derived from the rules that apply to a typical Indonesian SME.

### 2.1 PPN (Pajak Pertambahan Nilai — VAT)

| Aspect | Rule | Authority |
| --- | --- | --- |
| **Nominal rate** | 12% (from 2025) | UU HPP (Law 7/2021) |
| **Effective rate for non-luxury goods/services** | **11%** — a DGT regulation fixes the effective rate at 11/12 × 12% for all non-luxury supplies | PMK 131/2024 |
| **Luxury goods** | Full 12% (plus PPnBM where applicable) | PMK 131/2024 |
| **PKP threshold** | Businesses with turnover **above Rp 4.8 billion** must register as PKP (Pengusaha Kena Pajak) and charge PPN | PP 1/2025 (moved up from the old Rp 4.8B BKP/JKP rules) |
| **Filing** | Monthly SPT Masa PPN by the end of the following month; e-Faktur upload by the 15th (or 20th) of the following month | — |
| **NSFP** | 17-digit serial numbers allocated by DJP, used sequentially | — |
| **Coretax** | DJP's new national tax administration system, live since January 2025; replaces the legacy e-Faktur desktop application for submission | — |

**Implication for the seed set:** We seed both `PPN 11%` and `PPN 12%`. `PPN 11%` is the effective rate that applies to the overwhelming majority of SME sales (non-luxury). `PPN 12%` exists for luxury-goods sellers and for forward compatibility if the DGT rate regulation is revoked.

### 2.2 PPh 23 (Withholding on Services, Royalties, Interest)

| Aspect | Rule |
| --- | --- |
| **Rate** | 2% of gross for services/royalties (2% is the current general rate since 2020 for most services), 15% for dividends/interest/royalties paid to non-residents (PPh 26) |
| **Payer** | Withholds from the payment to the service provider and remits to DJP |
| **Applicability** | SMEs routinely withhold 2% from vendors for consulting, marketing, rental, transportation, and other services |

Seeded as **`PPH 23`**, rate `2`, `is_pph = true`. This is the most common PPh that a trade/service SME will encounter.

### 2.3 PPh Final 0.5% (PP 23/2018, as revised by PP 20/2026)

| Aspect | Rule |
| --- | --- |
| **Rate** | **0.5%** of gross turnover (peredaran bruto) — *final* income tax (no reconciliation to normal PPh) |
| **Eligible taxpayers** | Under **PP 20/2026** (effective for fiscal years beginning on/after 1 Jan 2026): **WP Orang Pribadi (individuals), PT Perorangan (sole-proprietor PT), and Koperasi with turnover ≤ Rp 4.8 billion** are still eligible. **CV, Firm (Fa), and PT (regular entities) are excluded** and fall back to normal PPh 25/29 regimes |
| **Turnover exemption** | For individual taxpayers, the **first Rp 500 million of annual turnover is exempt** from this final tax |
| **Payment** | Self-assessed monthly, due by the 15th of the following month, paid via Coretax |
| **Reason we seed it** | Invent-MAG's UMKM target is dominated by sole proprietors (WP OP) and small koperasi — the exact group PP 20/2026 continues to serve. CV/PT users should use PPh 25 manual entries instead (see §9, Caveat) |

Seeded as **`PPh Final 0.5%`**, `rate = 0.5`, `is_pph = true`.

> **Why this replaced the old `PPH 22` seed (2026-08-05):** `PPH 22` (1.5% withholding on goods purchases from importers/manufacturers) is narrowly applicable and rarely applies to a retail/trade SME's *sales* invoices. `PPh Final 0.5%` is the income tax a typical UMKM actually books. Existing `PPH 22` records were converted **in place** (same row, new name/rate) so that `pph_tax_id` references remain valid; per-document snapshots (`pph_rate`/`pph_amount`) were already stored at transaction time and are therefore unaffected. `PPH 22` remains *creatable* from the Tax page for the minority who need it — it is simply no longer seeded.

### 2.4 PPh 21 (Employee Withholding) — Planned

Correct UMKM scope is a flat **"Payroll Tax" picker** on the payroll screen (a withholding line in the payment journal) — **not** a full TER/PTKP/1721-II calculation engine. The UMKM target does not need a salary-tax engine. See `docs/ROADMAP.md`.

### 2.5 PPh 25 (Monthly Installments) — Deferred

A monthly installment via manual journal entry is sufficient for the UMKM target; a dedicated calculation engine is out of scope.

---

## 3. Seed Set Rationale

Two mechanisms create the seed records:

1. **`TaxService::ensureIndonesianDefaults()`** — called (idempotently) whenever a tenant switches `tax_jurisdiction` to `'ID'`. Creates the **three compliance-critical records** if missing: `PPN 11%`, `PPH 23 (2%)`, `PPh Final 0.5%`. Never duplicates and never overrides an existing default.
2. **`TaxSeeder`** — used on fresh installs / in tests. `updateOrCreate`s the **full six-record catalog**: `PPN 11%`, `PPN 12%`, `PPH 23 (2%)`, `PPh Final 0.5%`, `Non-PKP`, `PPN Inclusive`.

| Seed record | rate | type | is_ppn | is_pph | Justification |
| --- | --- | --- | --- | --- | --- |
| `PPN 11%` | 11 | exclusive | true | false | Effective VAT rate for non-luxury supplies (PMK 131/2024). **Default** tax. |
| `PPN 12%` | 12 | exclusive | true | false | Luxury goods; forward compatibility |
| `PPN Inclusive` | 11 | inclusive | false | false | Inclusive pricing (tax already inside item prices). Deliberately *not* `is_ppn` — see Caveat #3 |
| `Non-PKP` | 0 | none | false | false | Explicit "no VAT" option for small sellers below the PKP threshold |
| `PPH 23` | 2 | exclusive | false | true | Service/royalty withholding, 2% general rate |
| `PPh Final 0.5%` | 0.5 | exclusive | false | true | UMKM final income tax (PP 23/2018, PP 20/2026) |

**Design decisions:**

1. **Two PPh records, not three.** PPh 21 (payroll) and PPh 25 (installments) are deliberately **not** seeded — without SPT reporting to distinguish them, additional types would post indistinguishably to the same PPh Payable (2150) account and *decrease* accounting accuracy. PPh 22 is not seeded (see §2.3).
2. **`PPN 11%` is the default** — but an existing default is *never* overridden by the auto-seed.
3. **`Non-PKP`** gives small sellers a first-class "no VAT" choice rather than an empty dropdown.
4. **`PPN Inclusive` is a general inclusive tax, not a PPN tax.** It shares the same inclusive-calculation mechanism but is deliberately not flagged `is_ppn`, so it never triggers e-Faktur/PPN features nor the PPN Masukan purchase branch (Caveat #3 stays unreachable).

---

## 4. The Universal Tax Model

### 4.1 One Table, Two Flags

There is a single `taxes` table. A tax is categorized by two booleans:

```
Tax:
  ├── is_ppn  (boolean) → PPN compliance features (Faktur Pajak, e-Faktur, NPWP, SPT)
  ├── is_pph  (boolean) → PPh compliance features (withholding, PPh Payable 2150)
  └── rate, type (exclusive/inclusive/none), name
```

This replaced a legacy **separate PPN toggle** that diverged from the tax system (see `docs/PPN_EVOLUTION.md` §"Problems We Found"). **PPN is a property of the selected tax — never a standalone switch.**

### 4.2 Applied to Every Transaction Type

The same `tax_id` selection drives calculation and accounting on:

- **Sales** (`SalesService`) — sales/order journal + PPN split
- **POS** (`PosService`) — POS journal + PPN split
- **Purchase Orders / Purchase Invoices** (`PurchaseService`) — PPN Masukan + PPh
- **Sales Returns** (`SalesReturnService`) — proportional reversals
- **Quotations** — tax snapshot only (no journal)

### 4.3 Snapshots

`ppn_enabled/ppn_rate/ppn_amount` and `pph_enabled/pph_rate/pph_amount` are **denormalized snapshots** copied from the selected Tax at creation time. They exist for historical accuracy (a Tax record edited later must not rewrite history) and performance. Helpers `Sales::isPpn()` / `Sales::isPph()` read the snapshot **only** — never the live `Tax` relationship.

**Critical:** toggling a Tax record's flags or switching `tax_jurisdiction` **never retroactively changes existing documents** — the snapshot is the source of truth for history.

### 4.4 PPN and General Tax Are Mutually Exclusive; PPh Is Independent

- One invoice = one `tax_id`. A PPN tax (`is_ppn`) or a general/none tax, never both.
- **PPh is a separate, parallel dimension.** An invoice can carry PPN **and** PPh simultaneously (both snapshot sets present, both journals posted) — PPh uses its own flag and its own accounts (1170 receivable / 2150 payable) and never conflicts with the PPN/general exclusivity rule.

---

## 5. How Each Tax Flows Through the System

### 5.1 PPN 11% / PPN 12% (Keluaran — on sales)

1. Tenant must be `isIndonesian()` **and** the tax selected must have `is_ppn = true`.
2. `SalesService::createSale()` derives and stores the PPN snapshot and posts the **5-leg VAT-split journal** when `ppn_keluaran_account_id` is configured (see §6.1).
3. The sale view shows the PPN line; e-Faktur features (NPWP, PKP, NSFP, Faktur Pajak generation) unlock.
4. **Faktur Pajak generation is now compliance-only** — it no longer posts a PPN journal (that moved to sale creation, Phase 1E). See `docs/PPN_EVOLUTION.md` Phase 1E.
5. `getSptMasaSummary()` rolls Keluaran and Masukan into the SPT Masa PPN report.

### 5.2 PPN Masukan (Input VAT — on purchases)

- Mirrors Keluaran but **on the asset side**: `Dr Input VAT (1160)`, inventory debited at **net** value.
- Only applied when the tenant `isIndonesian()` and the PO's selected tax has `is_ppn = true`.
- Reduces the SPT Masa PPN payable (`Keluaran − Masukan`, floored at 0; excess = "Lebih Bayar").

### 5.3 PPh 23 / PPh Final 0.5% (withholding)

- `is_pph = true` tax selected → `pph_*` snapshot stored on the document.
- PPh **subtracts** from the total (the payer owes less because tax is withheld at source).
- **Sale side:** `Dr PPh Receivable (1170) / Cr Accounts Receivable` posted at invoice time (Mekari pattern) — the customer owes the net-of-withholding amount.
- **Purchase side:** `Cr PPh Payable (2150)` alongside `Dr Inventory / Cr AP` — the company withholds from the supplier and remits to DJP.
- PPh types share two accounts (1170 receivable / 2150 payable) — distinguished only by the seeded name/rate; see §9 Caveat #4.

### 5.4 Non-PKP / general / no tax

- `rate = 0` or `is_ppn = is_pph = false` → no PPN/PPh features, no special journal legs. Tax may still be computed into `total_tax` per the universal model.

---

## 6. Journal Entry Accounting

### 6.1 Sale with PPN (VAT split) — 5 legs

Sale of 200 + PPN 11% = 22, grand total 222:

| Debit | Credit |
| --- | --- |
| Accounts Receivable — 222 | |
| | Sales Revenue — 200 (net of VAT) |
| | PPN Keluaran — 22 (liability) |
| Cost of Goods Sold — X | |
| | Inventory — X |

- `sourceable = Sales`.
- Revenue is booked **net of VAT** — this is the Phase 1E fix that resolved the "revenue booked gross" audit caveat.
- **Fallback:** if `ppn_keluaran_account_id` is not configured, the system posts the legacy **4-leg gross-revenue journal** (`Dr AR gross / Cr Revenue gross + COGS/Inventory`) instead of crashing.

### 6.2 POS Sale with PPN

Identical VAT split in the POS journal (also 5 legs). `sourceable = Sales` (type `pos`).

### 6.3 Sale with PPh

```
Dr. PPh Receivable (1170-{tid})   pph_amount
Cr. Accounts Receivable           pph_amount
```

Standalone entry, independent of PPN. Posted at invoice time; the credit reduces the net amount the customer owes.

### 6.4 Purchase with PPN Masukan + PPh

Purchase 200 + PPN 11% (exclusive) = 22:

| Debit | Credit |
| --- | --- |
| Inventory (1140) — 200 (net) | |
| Input VAT / PPN Masukan (1160) — 22 | |
| | Accounts Payable (2110) — 222 |
| | PPh Payable (2150) — pph (if withholding applies) |

### 6.5 Sales Return with PPN / PPh

Proportional reversal based on `return_amount / sale.total`:

```
Dr. PPN Keluaran          proportional_ppn
Cr. Accounts Receivable   proportional_ppn
```

PPN is reversed on return **whenever the original sale `isPpn()`** — a Faktur Pajak is no longer required (Phase 1E). Legacy FakturPajak-sourced PPN journals are still reversed by `cancelFakturPajak()`.

---

## 7. Calculation Rules

- **DPP (taxable base) = `sale.total - sale.total_tax`** for both exclusive and inclusive types.
- **PPh taxable base:** subtotal − order discount, **before** PPN is added (correct for PPh Final, whose peredaran bruto excludes VAT).
- **PPh is always exclusive** — computed on the pre-VAT base.
- **Faktur Pajak PPN = `sale.total_tax`** directly — no independent recalculation (prevents FP/sale divergence).

---

## 8. Tax Groups & the Internationalization Phase

International ERPs (Odoo, QuickBooks, Zoho) use **tax groups** — a single "tax position" that bundles multiple rates (e.g., federal + state sales tax). Invent-MAG's current model is **single-tax-per-line**. A tax group abstraction is **documented but deferred** to a future internationalization phase:

- Would allow one `tax_id` to reference a *group* of child taxes, each posting to its own GL account.
- Only matters once we serve jurisdictions with multi-component taxes (US sales tax, EU VAT + eco-tax, etc.).
- **Not needed for Indonesia** (single PPN + single PPh) — do not build it yet.

---

## 9. Known Limitations (Audit Caveats)

These are documented findings from the 2026-08-05 audit. **Caveats #1 and #2 are resolved by the Phase 1E VAT-split fix.**

### Caveat #1 (RESOLVED) — PPN double-posted AR on Faktur Pajak issuance

`SalesService` posted `Dr AR / Cr Revenue` (gross) at sale creation **and** `EFakturService::postPpnKeluaranJournalEntry()` posted `Dr AR / Cr PPN Keluaran` at Faktur issuance. The AR leg was posted twice → phantom AR. **Fixed** by moving PPN recognition to sale creation and deleting the e-Faktur journal post.

### Caveat #2 (RESOLVED) — Revenue booked gross

Revenue was credited at the tax-inclusive total. **Fixed** by the 5-leg split (§6.1); revenue is now net of VAT for PPN sales, and remains gross for non-Indonesian / non-PPN sales. This also aligns the UMKM PPh Final gross-base reporting with the accounting books.

### Caveat #3 (DEFERRED, LOW) — Inclusive PPN on purchases double-counts inventory

`PurchaseService::createPurchase()` adds inclusive-PPN into inventory **and** also splits out PPN Masukan, double-counting inventory. **Currently unreachable** with the seed set (`PPN Inclusive` is the only inclusive record and it is deliberately *not* `is_ppn`), so a purchase selecting it never triggers the Masukan branch. Document only; do not fix until a seeded inclusive `is_ppn` record exists.

### Caveat #4 (KNOWN LIMITATION) — Shared PPh accounts

PPh Payable (2150) is the shared withholding liability **credited on the purchase side**; on the sale side the receivable is booked as `PPh Receivable (1170)` offset against AR. All PPh types post to these same two accounts. Distinct seeded types (PPh 23 vs PPh Final 0.5%) are therefore not separately reportable in the ledger. This is deliberate (fewer records = more accurate single liability); a PPh sub-ledger is future work.

### Caveat #5 (KNOWN LIMITATION) — POS lacks PPN snapshot fields

POS `Sales::create` does not persist `ppn_enabled/ppn_rate/ppn_amount`, so historical POS sales cannot reconstruct their PPN share from snapshots (they can via the journal). Pre-existing gap; not part of this change.

---

## 10. Reference: Data Model

| Table | Key fields | Purpose |
| --- | --- | --- |
| `taxes` | `name`, `rate`, `type`, `is_ppn`, `is_pph`, `is_default` | Tax definitions (seed set in §3) |
| `sales` | `tax_id`, `ppn_*`, `pph_*`, `total_tax` | Document snapshots |
| `po` | `tax_id`, `ppn_*`, `pph_*` | Document snapshots |
| `quotations` | `tax_id`, `pph_*` | Document snapshots |
| `faktur_pajak` | `sales_id`, `nsfp`, `dpp`, `ppn`, `status` | DJP tax invoice |
| `accounting_settings` | `ppn_keluaran_account_id`, `ppn_masukan_account_id`, `pph_receivable_account_id`, `pph_payable_account_id` | GL account wiring |

Seed account codes (per-tenant suffix): PPN Keluaran `2130-{tid}`, PPN Masukan `1160-{tid}`, PPh Receivable `1170-{tid}`, PPh Payable `2150-{tid}`, Inventory `1140-{tid}`, AR `1130-{tid}`, AP `2110-{tid}`.
