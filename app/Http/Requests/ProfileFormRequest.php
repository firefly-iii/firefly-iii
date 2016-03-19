<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class ProfileFormRequest extends Request
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
            'current_password'          => 'required',
            'new_password'              => 'required|confirmed',
            'new_password_confirmation' => 'required',
        ];
    }
}
