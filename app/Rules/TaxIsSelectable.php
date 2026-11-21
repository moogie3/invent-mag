<?php

namespace App\Rules;

use App\Models\Tax;
use App\Scopes\TenantScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The one check every document's tax fields share: sales, purchases,
 * quotations and POS all validated with a raw `exists:taxes,id`, which is a
 * table lookup with no idea who is asking. It said yes to a tax belonging to
 * another company, to a switched-off tax, and to a PPN/PPh record this
 * jurisdiction cannot use - and the writer then resolved the id to null and
 * booked 0% without a word.
 *
 * Tenant scope comes from the model itself, but the rule queries past it so
 * the message can say WHICH company the tax belongs to instead of claiming
 * the row does not exist.
 */
class TaxIsSelectable implements ValidationRule
{
    /**
     * @param  bool  $enforceJurisdiction  The PPh field keeps jurisdiction out
     *                                     of it on purpose: withholding is
     *                                     already gated by the service's
     *                                     isIndonesian() check, and the tests
     *                                     that pin that gate submit a PPh tax
     *                                     to a non-Indonesian tenant and expect
     *                                     the document to save without it.
     *                                     Nothing else gates the chosen tax
     *                                     rate, so tax_id has to.
     */
    public function __construct(private bool $enforceJurisdiction = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Absence is decided by the 'nullable' rule that travels with this
        // one; arriving here with nothing to check means nothing to reject.
        if ($value === null || $value === '') {
            return;
        }

        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;

        if (is_array($value)) {
            $fail(__('messages.tax_select_missing'));

            return;
        }

        $tax = $tenant !== null ? Tax::query()->whereKey($value)->first() : null;

        if ($tax === null) {
            $belongsToAnotherCompany = $tenant !== null
                && Tax::withoutGlobalScope(TenantScope::class)->whereKey($value)->exists();

            $fail(__('messages.'.($belongsToAnotherCompany ? 'tax_select_other_tenant' : 'tax_select_missing')));

            return;
        }

        if (! $tax->is_active) {
            $fail(__('messages.tax_select_inactive'));

            return;
        }

        if ($this->enforceJurisdiction
            && $tenant !== null
            && ! $tenant->isIndonesian()
            && ($tax->is_ppn || $tax->is_pph)) {
            $fail(__('messages.tax_select_jurisdiction'));
        }
    }
}
