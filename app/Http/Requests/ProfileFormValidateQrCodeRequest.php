<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileFormValidateQrCodeRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ProfileFormValidateQrCodeRequest extends ProfileFormRequestBase
{    
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code'                => 'required|digits:6',
        ];
    }
}
