<?php
/**
 * UserRegistrationRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class UserRegistrationRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class UserRegistrationRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only everybody
        return true;
    }

    /**
     * @return array
     */
    public function getUserData(): array
    {
        return [
            'email'    => $this->string('email'),
            'password' => $this->string('password'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'email'    => 'email|required',
            'password' => 'confirmed|secure_password',

        ];
    }
}
