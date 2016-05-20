<?php
/**
 * TokenFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class TokenFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class TokenFormRequest extends Request
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

        $rules = [
            'code' => 'required|2faCode',
        ];

        return $rules;
    }
}
