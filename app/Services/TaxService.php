<?php

namespace App\Services;

use App\Models\Tax;
use App\Models\Tenant;
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

    /**
     * Create the standard Indonesian tax records (PPN 11%, PPH 23 2% and
     * PPh Final 0.5%) for a tenant when they switch to the Indonesian tax
     * jurisdiction. Idempotent: existing PPN/PPh records are never duplicated,
     * and an existing default tax is left untouched.
     *
     * @return array<int, string> Names of the tax records that were created.
     */
    public function ensureIndonesianDefaults(Tenant $tenant): array
    {
        $created = [];

        if (! Tax::where('tenant_id', $tenant->id)->where('is_ppn', true)->exists()) {
            Tax::create([
                'name' => 'PPN 11%',
                'rate' => 11,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Pertambahan Nilai 11% (Standard VAT for Indonesia)',
                'is_active' => true,
                'is_default' => ! Tax::where('tenant_id', $tenant->id)->where('is_default', true)->exists(),
                'is_ppn' => true,
                'is_pph' => false,
            ]);
            $created[] = 'PPN 11%';
        }

        if (! Tax::where('tenant_id', $tenant->id)->where('is_pph', true)->exists()) {
            Tax::create([
                'name' => 'PPH 23',
                'rate' => 2,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Penghasilan Pasal 23 (Withholding tax on services)',
                'is_active' => true,
                'is_default' => false,
                'is_ppn' => false,
                'is_pph' => true,
            ]);
            $created[] = 'PPH 23';
        }

        if (! Tax::where('tenant_id', $tenant->id)->where('name', 'PPh Final 0.5%')->exists()) {
            Tax::create([
                'name' => 'PPh Final 0.5%',
                'rate' => 0.5,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Penghasilan Final 0,5% berdasarkan PP 23/2018 (UMKM, omzet ≤ Rp4,8 miliar)',
                'is_active' => true,
                'is_default' => false,
                'is_ppn' => false,
                'is_pph' => true,
            ]);
            $created[] = 'PPh Final 0.5%';
        }

        return $created;
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
