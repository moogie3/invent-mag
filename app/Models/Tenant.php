<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use UsesTenantConnection;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pkp_date' => 'date',
            'accounting_settings' => 'array',
            'backup_password' => 'encrypted',
        ];
    }

    // -------------------------------------------------------------------------
    // Accounting Settings Helpers
    // -------------------------------------------------------------------------

    public function getAccountingSetting(string $key): mixed
    {
        return $this->accounting_settings[$key] ?? null;
    }

    public function isAccountingConfigured(): bool
    {
        $required = [
            'sales_revenue_account_id',
            'accounts_receivable_account_id',
            'cost_of_goods_sold_account_id',
            'inventory_account_id',
            'accounts_payable_account_id',
            'cash_account_id',
        ];

        $settings = $this->accounting_settings ?? [];

        foreach ($required as $key) {
            if (empty($settings[$key])) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // -------------------------------------------------------------------------
    // Feature checks — personal standalone: every feature is always available
    // -------------------------------------------------------------------------

    public function hasFeature(string $feature): bool
    {
        return true;
    }

    // -------------------------------------------------------------------------
    // Jurisdiction
    // -------------------------------------------------------------------------

    /**
     * Check if this tenant operates under Indonesian tax jurisdiction.
     * Controls visibility of PPN, Faktur Pajak, NPWP, and all DJP compliance features.
     */
    public function isIndonesian(): bool
    {
        return $this->tax_jurisdiction === 'ID';
    }

    /**
     * Check if switching away from Indonesian jurisdiction is safe.
     * Blocks the switch if there are draft Faktur Pajak or unissued PPN sales.
     *
     * @return array{safe: bool, message: string|null, count: int}
     */
    public function canDisableIndonesianJurisdiction(): array
    {
        $draftCount = FakturPajak::where('status', 'draft')->count();
        if ($draftCount > 0) {
            return [
                'safe' => false,
                'message' => __('messages.efaktur_cannot_disable_with_drafts', ['count' => $draftCount]),
                'count' => $draftCount,
            ];
        }

        $unissuedCount = Sales::where('ppn_enabled', true)
            ->where('ppn_amount', '>', 0)
            ->whereDoesntHave('fakturPajak')
            ->count();
        if ($unissuedCount > 0) {
            return [
                'safe' => false,
                'message' => __('messages.efaktur_cannot_disable_with_unissued', ['count' => $unissuedCount]),
                'count' => $unissuedCount,
            ];
        }

        return ['safe' => true, 'message' => null, 'count' => 0];
    }

    // -------------------------------------------------------------------------
    // Backup Configuration
    // -------------------------------------------------------------------------

    public function hasBackupPassword(): bool
    {
        return ! empty($this->backup_password);
    }

    public function isBackupEncrypted(): bool
    {
        return $this->backup_encrypted && $this->hasBackupPassword();
    }
}
