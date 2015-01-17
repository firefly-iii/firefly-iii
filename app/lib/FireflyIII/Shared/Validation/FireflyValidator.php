<?php
namespace FireflyIII\Shared\Validation;

use Illuminate\Validation\Validator;

/**
 * Class FireflyValidator
 *
 * @package FireflyIII\Shared\Validation
 */
class FireflyValidator extends Validator
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateAlphabasic($attribute, $value, $parameters)
    {
        $pattern = '/[^[:alnum:]_\-\.\& \(\)\'"]/iu';
        if (preg_match($pattern, $value)) {
            return false;
        } else {
            return true;
        }
    }
}
