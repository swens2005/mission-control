<?php

namespace App\Rules;

use App\Support\Money\Quantity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A positive quantity: whole pieces, or hours in quarter steps.
 */
class QuarterQuantity implements ValidationRule
{
    public function __construct(private bool $wholeOnly = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hundredths = is_string($value) || is_int($value) ? Quantity::parse((string) $value) : null;
        $step = $this->wholeOnly ? 100 : Quantity::STEP;

        if ($hundredths === null || $hundredths === 0) {
            $fail($this->wholeOnly ? 'Enter a whole number, like 1 or 3.' : 'Enter a number of hours, like 1 or 1.5.');
        } elseif ($hundredths % $step !== 0) {
            $fail($this->wholeOnly ? 'Fixed-price items come in whole numbers.' : 'Use quarter hours: 0.25, 0.5 or 0.75.');
        } elseif ($hundredths > Quantity::MAX) {
            $fail('That’s more than '.Quantity::format(Quantity::MAX).'.');
        }
    }
}
