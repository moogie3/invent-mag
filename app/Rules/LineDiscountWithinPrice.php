<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The one check every document's line discounts share. `products` was
 * validated as `required|json` and nothing looked inside, so a fixed
 * discount larger than the line price (or a percentage above 100) was
 * written straight through: the line total went negative, and with it the
 * subtotal, the grand total and the journal lines built from them.
 *
 * The payload carries two spellings for the price and for the discount
 * type, because sales send `customer_price`/`discountType` while purchases
 * and quotations send `price`/`discount_type`.
 */
class LineDiscountWithinPrice implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // The `json` rule reports a payload that will not decode; arriving
        // here with anything else means there is no line to check.
        $lines = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($lines)) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $price = (float) ($line['price'] ?? $line['customer_price'] ?? 0);
            $discount = (float) ($line['discount'] ?? 0);
            $discountType = $line['discount_type'] ?? $line['discountType'] ?? 'fixed';

            if ($discount < 0) {
                $fail('The discount on line '.($index + 1).' cannot be negative.')->translate();

                return;
            }

            $tooLarge = $discountType === 'percentage'
                ? $discount > 100
                : $discount > $price;

            if ($tooLarge) {
                $fail('The discount on line '.($index + 1).' cannot be larger than the line itself.')->translate();

                return;
            }
        }
    }
}
