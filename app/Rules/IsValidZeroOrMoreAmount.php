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
        $value = (string)$value;
        // must not be empty:
        if($this->emptyString($value)) {
            $fail('validation.filled')->translate();
            Log::info(sprintf('IsValidZeroOrMoreAmount: "%s" cannot be empty.', $value));

            return;
        }

        // must be a number:
        if(!$this->isValidNumber($value)) {
            $fail('validation.numeric')->translate();
            Log::info(sprintf('IsValidZeroOrMoreAmount: "%s" is not a number.', $value));

            return;
        }
        // must not be scientific notation:
        if($this->scientificNumber($value)) {
            $fail('validation.scientific_notation')->translate();
            Log::info(sprintf('IsValidZeroOrMoreAmount: "%s" cannot be in the scientific notation.', $value));

            return;
        }
        // must be zero or more
        if(!$this->zeroOrMore($value)) {
            $fail('validation.more_than_zero_correct')->translate();
            Log::info(sprintf('IsValidZeroOrMoreAmount: "%s" must be zero or more.', $value));

            return;
        }
        // must be less than 100 million and 1709:
        if($this->moreThanLots($value)) {
            Log::info(sprintf('IsValidPositiveAmount: "%s" must be less than %s.', $value, self::BIG_AMOUNT));
            $fail('validation.lte.numeric')->translate(['value' => self::BIG_AMOUNT]);
        }
        Log::info(sprintf('IsValidZeroOrMoreAmount: "%s" is a valid positive amount.', $value));
    }
}
