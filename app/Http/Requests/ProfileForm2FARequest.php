<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileForm2FARequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ProfileForm2FARequest extends ProfileFormRequestBase
{    
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'enable_2fa'                => 'boolean',
        ];
    }
}