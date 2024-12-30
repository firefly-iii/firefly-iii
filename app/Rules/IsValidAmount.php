<?php

declare(strict_types=1);

namespace FireflyIII\Rules;

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class IsValidAmount implements ValidationRule
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
            $message = sprintf('IsValidAmount: "%s" cannot be empty.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must be a number:
        if (!$this->isValidNumber($value)) {
            $fail('validation.numeric')->translate();
            $message = sprintf('IsValidAmount: "%s" is not a number.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must not be scientific notation:
        if ($this->scientificNumber($value)) {
            $fail('validation.scientific_notation')->translate();
            $message = sprintf('IsValidAmount: "%s" cannot be in the scientific notation.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must be more than minus a lots:
        if ($this->lessThanLots($value)) {
            $amount  = bcmul('-1', self::BIG_AMOUNT);
            $fail('validation.gte.numeric')->translate(['value' => $amount]);
            $message = sprintf('IsValidAmount: "%s" must be more than %s.', $value, $amount);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must be less than a large number
        if ($this->moreThanLots($value)) {
            $fail('validation.lte.numeric')->translate(['value' => self::BIG_AMOUNT]);
            $message = sprintf('IsValidAmount: "%s" must be more than %s.', $value, self::BIG_AMOUNT);
            Log::debug($message);
            Log::channel('audit')->info($message);
        }
        Log::debug(sprintf('IsValidAmount: "%s" is a valid positive amount.', $value));
    }
}
