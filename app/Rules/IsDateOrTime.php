<?php
declare(strict_types=1);

namespace FireflyIII\Rules;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class IsDateOrTime
 */
class IsDateOrTime implements Rule
{

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return (string)trans('validation.date_or_time');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $value = (string)$value;
        if (10 === \strlen($value)) {
            // probably a date format.
            try {
                Carbon::createFromFormat('Y-m-d', $value);
            } catch (InvalidDateException $e) {
                Log::error(sprintf('"%s" is not a valid date: %s', $value, $e->getMessage()));

                return false;
            }

            return true;
        }
        // is an atom string, I hope?
        try {
            Carbon::parse($value);
        } catch (InvalidDateException $e) {
            Log::error(sprintf('"%s" is not a valid date or time: %s', $value, $e->getMessage()));

            return false;
        }

        return true;
    }
}