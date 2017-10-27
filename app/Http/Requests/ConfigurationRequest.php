<?php
/**
 * ConfigurationRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

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
            'single_user_mode' => $this->boolean('single_user_mode'),
            'is_demo_site'     => $this->boolean('is_demo_site'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed
        $rules = [
            'single_user_mode' => 'between:0,1|numeric',
            'is_demo_site'     => 'between:0,1|numeric',
        ];

        return $rules;
    }
}
