<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class AccountFormRequest
 *
 * @package FireflyIII\Http\Requests
 */
class AccountFormRequest extends Request
{
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    public function rules()
    {
        return [
            'name'          => 'required|between:1,100|uniqueForUser:accounts,name',
            'openingBalance' => 'required|numeric'
        ];
    }
}