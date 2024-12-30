<?php

declare(strict_types=1);

namespace FireflyIII\Rules;

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class IsValidZeroOrMoreAmount implements ValidationRule
{
    use ValidatesAmountsTrait;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = (string) $value;
        // must not be empty:
        if ($this->emptyString($value)) {
            $fail('validation.filled')->translate();
            $message = sprintf('IsValidZeroOrMoreAmount: "%s" cannot be empty.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must be a number:
        if (!$this->isValidNumber($value)) {
            $fail('validation.numeric')->translate();
            $message = sprintf('IsValidZeroOrMoreAmount: "%s" is not a number.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must not be scientific notation:
        if ($this->scientificNumber($value)) {
            $fail('validation.scientific_notation')->translate();
            $message = sprintf('IsValidZeroOrMoreAmount: "%s" cannot be in the scientific notation.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must be zero or more
        if (!$this->zeroOrMore($value)) {
            $fail('validation.more_than_zero_correct')->translate();
            $message = sprintf('IsValidZeroOrMoreAmount: "%s" must be zero or more.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must be less than a large number
        if ($this->moreThanLots($value)) {
            $fail('validation.lte.numeric')->translate(['value' => self::BIG_AMOUNT]);
            $message = sprintf('IsValidPositiveAmount: "%s" must be less than %s.', $value, self::BIG_AMOUNT);
            Log::debug($message);
            Log::channel('audit')->info($message);
        }
        Log::debug(sprintf('IsValidZeroOrMoreAmount: "%s" is a valid positive amount.', $value));
    }
}
