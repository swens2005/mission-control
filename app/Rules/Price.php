<?php

namespace App\Rules;

use App\Support\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A euro amount as people type it ("1250", "1.250,50"), within reason.
 */
class Price implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cents = is_string($value) || is_int($value) ? Money::parse((string) $value) : null;

        if ($cents === null) {
            $fail('Enter an amount like 1250 or 1.250,50.');
        } elseif ($cents > Money::MAX_CENTS) {
            $fail('That’s more than '.Money::format(Money::MAX_CENTS).'.');
        }
    }
}
