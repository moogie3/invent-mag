<?php

namespace App\Services;

use App\Helpers\PurchaseHelper;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What stock on hand is worth, on the same basis the purchase journal
 * capitalized it.
 *
 * The Inventory account was fed by one arithmetic (the supplier invoice:
 * order discount taken off, tax added back only when it is not recoverable
 * input VAT) while batches were costed at the raw line price, so COGS, a
 * stock opname and a manual correction could each produce a different
 * number for the same units. Every inventory writer and the stock-value
 * figure now read the one formula held here, which is what makes the
 * Inventory account comparable with the shelf.
 */
class StockValuationService
{
    /**
     * Per-request memo of each purchase's capitalization factor, so costing
     * five batches from two purchase orders costs two lookups, not ten.
     *
     * @var array<int, float>
     */
    protected array $factors = [];

    /** @var bool|null memoized answer to inputVatIsPosted(), once per request */
    protected ?bool $inputVatPosted = null;

    public function __construct(protected AccountingService $accountingService) {}

    /**
     * What one unit of a purchase line cost the business.
     *
     * The line's total, scaled by how much of the purchase actually landed
     * in Inventory, split over the line's quantity. With no order discount
     * and no tax the factor is 1 and this is simply total / quantity.
     *
     * @param  object  $item  a POItem or a po_items row (po_id, quantity, total, price)
     */
    public function unitCost(object $item): float
    {
        $quantity = (int) ($item->quantity ?? 0);

        if ($quantity <= 0) {
            return (float) ($item->price ?? 0);
        }

        $lineTotal = (float) ($item->total ?? 0);

        if ($lineTotal <= 0) {
            // Free goods cost nothing; they must not be valued at the
            // catalogue price just because no money changed hands.
            return 0.0;
        }

        return ($lineTotal / $quantity) * $this->capitalizationFactor((int) ($item->po_id ?? 0));
    }

    /**
     * What the given units would cost right now, without touching batches.
     *
     * Consumes in the same order CostsLineItems::costAndConsumeBatches()
     * does (earliest expiry first, then oldest receipt), so a figure shown
     * while an opname is still open is the figure its journal will book.
     */
    public function costOfUnits(int $productId, int|float $quantity, float $fallbackUnitCost): float
    {
        $walk = $this->walkBatches($productId, $quantity);

        return $walk['cost'] + $walk['uncovered'] * $fallbackUnitCost;
    }

    /**
     * What the given units cost when their batches still cover all of
     * them, null when they do not.
     *
     * Goods the shelf no longer holds (fully sold out, or stock that
     * arrived without a batch) have no batch cost to book. Returning null
     * instead of padding with a fallback keeps that decision with the
     * caller, which knows what such units are worth in its own context,
     * rather than baking one assumption into the number.
     */
    public function costOfCoveredUnits(int $productId, int|float $quantity): ?float
    {
        $walk = $this->walkBatches($productId, $quantity);

        return $walk['uncovered'] > 0 ? null : $walk['cost'];
    }

    /**
     * The shared walk behind both costing methods: what the covered units
     * cost, and how many units no batch reached.
     *
     * @return array{cost: float, uncovered: float}
     */
    private function walkBatches(int $productId, int|float $quantity): array
    {
        $remaining = (float) $quantity;

        if ($remaining <= 0) {
            return ['cost' => 0.0, 'uncovered' => 0.0];
        }

        $cost = 0.0;

        foreach ($this->batchesFor($productId) as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->remaining_quantity, $remaining);
            $cost += $take * $this->unitCost($batch);
            $remaining -= $take;
        }

        return ['cost' => $cost, 'uncovered' => $remaining];
    }

    /**
     * What one product's physical stock is worth: the units still on their
     * batches at batch cost, and anything no batch covers (stock added by a
     * correction or an opname, which never creates a batch) at the buying
     * price, which is the same fallback a sale costs out-of-batch units at.
     */
    public function valueOfProduct(int $productId): float
    {
        $left = (float) DB::table('product_warehouse')
            ->where('tenant_id', app('currentTenant')->id)
            ->where('product_id', $productId)
            ->sum('quantity');

        if ($left <= 0) {
            return 0.0;
        }

        $value = 0.0;

        foreach ($this->batchesFor($productId) as $batch) {
            if ($left <= 0) {
                break;
            }

            $take = min((float) $batch->remaining_quantity, $left);
            $value += $take * $this->unitCost($batch);
            $left -= $take;
        }

        if ($left > 0) {
            $value += $left * (float) (Product::where('id', $productId)->value('price') ?? 0);
        }

        return $value;
    }

    /**
     * The whole warehouse, in bounded aggregates: no per-product loop, no
     * N+1, four queries whatever the catalogue size.
     */
    public function valueOfStock(): float
    {
        $tenantId = app('currentTenant')->id;

        $physical = DB::table('product_warehouse')
            ->where('tenant_id', $tenantId)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->get()
            ->keyBy('product_id');

        if ($physical->isEmpty()) {
            return 0.0;
        }

        $prices = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->select('id', 'price')
            ->get()
            ->keyBy('id');

        $batches = DB::table('po_items')
            ->where('tenant_id', $tenantId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->warmFactors($batches->pluck('po_id')->all());

        $byProduct = $batches->groupBy('product_id');
        $value = 0.0;

        foreach ($physical as $productId => $row) {
            $left = (float) $row->qty;

            foreach ($byProduct->get($productId, []) as $batch) {
                if ($left <= 0) {
                    break;
                }

                $take = min((float) $batch->remaining_quantity, $left);
                $value += $take * $this->unitCost($batch);
                $left -= $take;
            }

            if ($left > 0) {
                $value += $left * (float) ($prices->get($productId)->price ?? 0);
            }
        }

        return $value;
    }

    /**
     * What the posted general ledger holds in the tenant's Inventory
     * account, or null when no account is configured (a tenant that keeps
     * no books cannot be reconciled, and must not be told it is wrong).
     */
    public function glInventoryBalance(): ?float
    {
        $tenant = app('currentTenant');
        $accountId = $tenant?->getAccountingSetting('inventory_account_id');

        if (! $accountId) {
            return null;
        }

        $balance = DB::table('transactions as t')
            ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
            ->where('je.tenant_id', $tenant->id)
            ->where('je.status', JournalEntry::STATUS_POSTED)
            ->where('t.account_id', $accountId)
            ->selectRaw("SUM(CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END) AS total")
            ->value('total');

        return (float) ($balance ?? 0);
    }

    /**
     * Positive: the books claim more stock than the shelf holds. Negative:
     * the shelf holds stock the books have never heard of. Null: no
     * Inventory account to compare against.
     */
    public function drift(): ?float
    {
        $gl = $this->glInventoryBalance();

        if ($gl === null) {
            return null;
        }

        return $gl - $this->valueOfStock();
    }

    /**
     * Book a physical stock correction: goods that left are an expense and
     * reduce Inventory, goods that arrived increase Inventory and reduce
     * the expense, exactly as StockOpnameService has always booked a
     * variance. Silent when accounting is not configured, again like
     * opname, so a tenant without books trades normally.
     *
     * @param  float  $increase  value of stock that appeared, at buying price
     * @param  float  $decrease  value of stock that left, at batch cost
     */
    public function postCorrection(float $increase, float $decrease, string $description, ?Model $source = null): void
    {
        if ($increase <= 0 && $decrease <= 0) {
            return;
        }

        $tenant = app('currentTenant');

        if (! $tenant || ! $tenant->hasFeature('accounting')) {
            return;
        }

        $settings = $tenant->accounting_settings ?? [];
        $inventory = Account::find($settings['inventory_account_id'] ?? null);
        $expense = Account::find($settings['cost_of_goods_sold_account_id'] ?? null);

        if (! $inventory || ! $expense) {
            return;
        }

        $transactions = [];

        if ($decrease > 0) {
            $transactions[] = ['account_name' => $expense->name, 'type' => 'debit', 'amount' => $decrease];
            $transactions[] = ['account_name' => $inventory->name, 'type' => 'credit', 'amount' => $decrease];
        }

        if ($increase > 0) {
            $transactions[] = ['account_name' => $inventory->name, 'type' => 'debit', 'amount' => $increase];
            $transactions[] = ['account_name' => $expense->name, 'type' => 'credit', 'amount' => $increase];
        }

        $this->accountingService->createJournalEntry($description, now(), $transactions, $source);
    }

    /**
     * The batches of one product in costing order: earliest expiry first,
     * then oldest receipt. Matches CostsLineItems exactly, so a valuation
     * and a consumption walk the same list.
     *
     * @return array<object>
     */
    protected function batchesFor(int $productId): array
    {
        return POItem::where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();
    }

    /**
     * Load the capitalization factor for every purchase these batches came
     * from, in two queries, so valuing the whole warehouse never issues a
     * lookup per batch.
     *
     * @param  array<int>  $poIds
     */
    protected function warmFactors(array $poIds): void
    {
        $wanted = collect($poIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->reject(fn ($id) => isset($this->factors[$id]))
            ->values();

        if ($wanted->isEmpty()) {
            return;
        }

        $pos = $this->constrainToCurrentTenant(DB::table('po'))
            ->whereIn('id', $wanted->all())
            ->get(['id', 'discount_total', 'discount_total_type', 'tax_rate', 'tax_type', 'ppn_enabled', 'ppn_amount']);

        $subtotals = $this->constrainToCurrentTenant(DB::table('po_items'))
            ->whereIn('po_id', $wanted->all())
            ->groupBy('po_id')
            ->selectRaw('po_id, SUM(total) as st')
            ->get()
            ->keyBy('po_id');

        foreach ($pos as $po) {
            $this->factors[(int) $po->id] = $this->factorFor($po, (float) ($subtotals->get($po->id)->st ?? 0));
        }
    }

    /**
     * The share of a purchase's subtotal that reached the Inventory
     * account, computed exactly as PurchaseService computes the debit, so
     * a batch is worth what the ledger was charged for it.
     */
    protected function capitalizationFactor(int $poId): float
    {
        if (isset($this->factors[$poId])) {
            return $this->factors[$poId];
        }

        $po = $this->constrainToCurrentTenant(DB::table('po'))
            ->where('id', $poId)
            ->first([
                'id', 'discount_total', 'discount_total_type', 'tax_rate', 'tax_type', 'ppn_enabled', 'ppn_amount',
            ]);

        if (! $po) {
            return $this->factors[$poId] = 1.0;
        }

        $subtotal = (float) $this->constrainToCurrentTenant(DB::table('po_items'))
            ->where('po_id', $poId)
            ->sum('total');

        return $this->factors[$poId] = $this->factorFor($po, $subtotal);
    }

    /**
     * The raw DB::table() calls in this service never see the Eloquent
     * tenant scope, so they have to reapply it themselves. Same condition
     * TenantScope uses: when a tenant is known the query is constrained
     * to it, and a context that runs without one keeps the unconstrained
     * behaviour it always had. Without this, a batch row whose po_id
     * pointed at another tenant's purchase had that purchase's private
     * order discount priced into this tenant's stock.
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function constrainToCurrentTenant($query)
    {
        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query;
    }

    protected function factorFor(object $po, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 1.0;
        }

        $orderDiscount = PurchaseHelper::calculateDiscount(
            $subtotal,
            $po->discount_total,
            $po->discount_total_type
        );
        $afterDiscount = $subtotal - $orderDiscount;
        $taxAmount = PurchaseHelper::calculateTaxAmount(
            $afterDiscount,
            (float) $po->tax_rate,
            $po->tax_type ?? 'none'
        );

        // Same arithmetic PurchaseService::postPurchaseJournalEntry()
        // capitalizes: an inclusive tax already sits inside the line
        // prices, an exclusive one joins the cost only when it is not
        // recoverable input VAT (PPN Masukan).
        $capitalized = ($po->tax_type ?? 'none') === 'inclusive'
            ? $afterDiscount
            : ($afterDiscount + $taxAmount);

        if (! empty($po->ppn_enabled) && (float) $po->ppn_amount > 0 && $this->inputVatIsPosted()) {
            $capitalized -= (float) $po->ppn_amount;
        }

        return max(0.0, $capitalized) / $subtotal;
    }

    /**
     * Whether a tenant's purchase input VAT has an account to be debited
     * to.
     *
     * PurchaseService only takes the VAT back out of Inventory when that
     * account exists; a tenant without one keeps the tax in the cost.
     * Answered once per request, or a deleted account would show up as a
     * drift that never happened.
     */
    protected function inputVatIsPosted(): bool
    {
        if ($this->inputVatPosted === null) {
            $accountId = app('currentTenant')?->getAccountingSetting('ppn_masukan_account_id');
            $this->inputVatPosted = (bool) $accountId && Account::find($accountId) !== null;
        }

        return $this->inputVatPosted;
    }
}
