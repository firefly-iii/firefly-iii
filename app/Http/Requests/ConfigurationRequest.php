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
    public function getConfigurationData(): array
    {
        return [
            'single_user_mode'     => intval($this->get('single_user_mode')) === 1,
            'must_confirm_account' => intval($this->get('must_confirm_account')) === 1,
            'is_demo_site'         => intval($this->get('is_demo_site')) === 1,
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'single_user_mode'     => 'between:0,1|numeric',
            'must_confirm_account' => 'between:0,1|numeric',
            'is_demo_site'         => 'between:0,1|numeric',
        ];

        return $rules;
    }
}
