<?php

namespace FireflyIII\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsBoolean
 */
class IsBoolean implements Rule
{
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.boolean');
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
        if (\is_bool($value)) {
            return true;
        }
        if (\is_int($value) && 0 === $value) {
            return true;
        }
        if (\is_int($value) && 1 === $value) {
            return true;
        }
        if (\is_string($value) && '1' === $value) {
            return true;
        }
        if (\is_string($value) && '0' === $value) {
            return true;
        }
        if (\is_string($value) && 'true' === $value) {
            return true;
        }
        if (\is_string($value) && 'false' === $value) {
            return true;
        }

        return false;
    }
}
