<?php

namespace App\Services;

use App\Models\Tax;
use Illuminate\Support\Facades\DB;

class TaxService
{
    public function getAllActive()
    {
        return Tax::active()->orderBy('name')->get();
    }

    public function getDefault()
    {
        return Tax::default()->first() ?? Tax::active()->first();
    }

    public function findById(int $id): ?Tax
    {
        return Tax::find($id);
    }

    public function create(array $data): Tax
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                Tax::where('tenant_id', app('currentTenant')->id)->update(['is_default' => false]);
            }

            return Tax::create([
                'name' => $data['name'],
                'rate' => $data['rate'],
                'type' => $data['type'] ?? Tax::TYPE_EXCLUSIVE,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
            ]);
        });
    }

    public function update(Tax $tax, array $data): Tax
    {
        return DB::transaction(function () use ($tax, $data) {
            if (! empty($data['is_default'])) {
                Tax::where('tenant_id', app('currentTenant')->id)
                    ->where('id', '!=', $tax->id)
                    ->update(['is_default' => false]);
            }

            $tax->update([
                'name' => $data['name'] ?? $tax->name,
                'rate' => $data['rate'] ?? $tax->rate,
                'type' => $data['type'] ?? $tax->type,
                'description' => $data['description'] ?? $tax->description,
                'is_active' => $data['is_active'] ?? $tax->is_active,
                'is_default' => $data['is_default'] ?? $tax->is_default,
            ]);

            return $tax->fresh();
        });
    }

    public function setDefault(Tax $tax): Tax
    {
        return DB::transaction(function () use ($tax) {
            Tax::where('tenant_id', app('currentTenant')->id)->update(['is_default' => false]);
            $tax->update(['is_default' => true]);

            return $tax->fresh();
        });
    }

    public function delete(Tax $tax): void
    {
        DB::transaction(function () use ($tax) {
            $hasReferences = $this->hasReferences($tax);

            if ($hasReferences) {
                $tax->update(['is_active' => false]);

                return;
            }

            $wasDefault = $tax->is_default;
            $tax->delete();

            if ($wasDefault) {
                $next = Tax::active()->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });
    }

    public function hasReferences(Tax $tax): bool
    {
        $tenantId = app('currentTenant')->id;

        $salesCount = DB::table('sales')->where('tax_id', $tax->id)->where('tenant_id', $tenantId)->count();
        $poCount = DB::table('po')->where('tax_id', $tax->id)->where('tenant_id', $tenantId)->count();
        $quotationCount = DB::table('quotations')->where('tax_id', $tax->id)->where('tenant_id', $tenantId)->count();
        $returnCount = DB::table('sales_returns')->where('tax_id', $tax->id)->where('tenant_id', $tenantId)->count();
        $purchaseReturnCount = DB::table('purchase_returns')->where('tax_id', $tax->id)->where('tenant_id', $tenantId)->count();

        return ($salesCount + $poCount + $quotationCount + $returnCount + $purchaseReturnCount) > 0;
    }
}
