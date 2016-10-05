<?php
/**
 * ConfigurationRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

/**
 * Class ConfigurationRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class ConfigurationRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users and admins
        return auth()->check() && auth()->user()->hasRole('owner');
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'single_user_mode' => 'between:0,1|numeric',
        ];

        return $rules;
    }
}
