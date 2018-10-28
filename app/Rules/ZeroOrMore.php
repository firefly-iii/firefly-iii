<?php

namespace FireflyIII\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 *
 * Class ZeroOrMore
 */
class ZeroOrMore implements Rule
{

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.zero_or_more');
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
        if ('' === $value) {
            return true;
        }
        $res = bccomp('0', $value);
        if ($res > 0) {
            return false;
        }

        return true;
    }
}
