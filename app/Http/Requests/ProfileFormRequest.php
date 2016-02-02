<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileFormRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ProfileFormRequest extends ProfileFormRequestBase
{    
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'current_password'          => 'required',
            'new_password'              => 'required|confirmed',
            'new_password_confirmation' => 'required',
        ];
    }
}
