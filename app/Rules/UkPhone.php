<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A UK number written any common way, or an international one starting with +. */
class UkPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if (uk_digits($value) !== null) {
            return;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($value, '+') && strlen($digits) >= 8 && strlen($digits) <= 15) {
            return;
        }
        $fail('Enter a phone number we can call, for example 01325 461241 or 07700 900123.');
    }
}
