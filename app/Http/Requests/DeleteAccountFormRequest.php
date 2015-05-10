<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class DeleteAccountFormRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class DeleteAccountFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'password' => 'required',
        ];
    }
}
