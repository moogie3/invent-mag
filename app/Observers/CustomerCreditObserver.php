<?php

namespace App\Observers;

use App\Models\CustomerCredit;

class CustomerCreditObserver
{
    /**
     * Handle the CustomerCredit "creating" event.
     * Set available_amount when creating a new credit.
     */
    public function creating(CustomerCredit $customerCredit): void
    {
        if (!isset($customerCredit->available_amount)) {
            $customerCredit->available_amount = $customerCredit->amount - ($customerCredit->used_amount ?? 0);
        }
    }

    /**
     * Handle the CustomerCredit "updating" event.
     * Keep available_amount in sync when amount or used_amount changes.
     */
    public function updating(CustomerCredit $customerCredit): void
    {
        // Only update available_amount if amount or used_amount changed
        if ($customerCredit->isDirty(['amount', 'used_amount'])) {
            $customerCredit->available_amount = $customerCredit->amount - $customerCredit->used_amount;
        }
    }

    /**
     * Handle the CustomerCredit "created" event.
     */
    public function created(CustomerCredit $customerCredit): void
    {
        //
    }

    /**
     * Handle the CustomerCredit "updated" event.
     */
    public function updated(CustomerCredit $customerCredit): void
    {
        //
    }

    /**
     * Handle the CustomerCredit "deleted" event.
     */
    public function deleted(CustomerCredit $customerCredit): void
    {
        //
    }
}
