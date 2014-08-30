<?php
namespace Firefly\Validation;

use Illuminate\Validation\Validator;

/**
 * Class FireflyValidator
 *
 * @package Firefly\Validation
 */
class FireflyValidator extends Validator
{
    public function validateAlphabasic($attribute, $value, $parameters)
    {
        $pattern = '/[^a-z_\-0-9 ]/i';
        if (preg_match($pattern, $value)) {
            return false;
        } else {
            return true;
        }
    }
}