<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every returned quantity on a return document has to be a whole number.
 *
 * `items` was validated as `required|json` and nothing looked inside, so a
 * crafted post could send 2.5: the item row casts the quantity to integer
 * (2.5 becomes 2) while the stock move and the journal total both use the
 * raw 2.5, so the shelf and the document disagreed by half a unit and the
 * return was worth more than the units it claimed to bring back. The form
 * already sends parseInt, so only a hand-built request could reach this.
 */
class WholeReturnQuantities implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // The `json` rule reports a payload that will not decode; arriving
        // here with anything else means there are no lines to check.
        $lines = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($lines)) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (! is_array($line) || ! array_key_exists('returned_quantity', $line)) {
                continue;
            }

            $quantity = $line['returned_quantity'];

            // Zero stays legal: the service drops non-positive lines itself,
            // and the form's own min is 0. Only a fraction (or a negative)
            // would put the shelf and the document out of step.
            if (! is_numeric($quantity) || (float) $quantity != (int) (float) $quantity || (float) $quantity < 0) {
                $fail('The quantity on line '.($index + 1).' must be a whole number.')->translate();

                return;
            }
        }
    }
}
