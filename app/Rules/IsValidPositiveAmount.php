<?php

declare(strict_types=1);

namespace FireflyIII\Rules;

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class IsValidPositiveAmount implements ValidationRule
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
            Log::debug(sprintf('IsValidPositiveAmount: "%s" cannot be empty.', $value));

            return;
        }

        // must be a number:
        if(!$this->isValidNumber($value)) {
            $fail('validation.numeric')->translate();
            Log::debug(sprintf('IsValidPositiveAmount: "%s" is not a number.', $value));
            return;
        }
        // must not be scientific notation:
        if($this->scientificNumber($value)) {
            $fail('validation.scientific_notation')->translate();
            Log::debug(sprintf('IsValidPositiveAmount: "%s" cannot be in the scientific notation.', $value));
            return;
        }
        // must be more than zero:
        if($this->lessOrEqualToZero($value)) {
            $fail('validation.more_than_zero')->translate();
            Log::debug(sprintf('IsValidPositiveAmount: "%s" must be more than zero.', $value));
            return;
        }
        // must be less than 100 million and 1709:
        if($this->moreThanLots($value)) {
            Log::debug(sprintf('IsValidPositiveAmount: "%s" must be less than %s.', $value, self::BIG_AMOUNT));
            $fail('validation.lte.numeric')->translate(['value' => self::BIG_AMOUNT]);
        }
        Log::debug(sprintf('IsValidPositiveAmount: "%s" is a valid positive amount.', $value));
    }
}
