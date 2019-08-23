<?php

namespace FireflyIII\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class LessThanPiggyTarget
 */
class LessThanPiggyTarget implements Rule
{
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.current_target_amount');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return true;
    }
}
