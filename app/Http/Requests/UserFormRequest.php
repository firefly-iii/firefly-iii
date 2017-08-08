<?php
/**
 * UserFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

/**
 * Class UserFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class UserFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getUserData(): array
    {
        return [
            'email'        => $this->string('email'),
            'blocked'      => $this->integer('blocked') === 1,
            'blocked_code' => $this->string('blocked_code'),
            'password'     => $this->string('password'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'id'           => 'required|exists:users,id',
            'email'        => 'email|required',
            'password'     => 'confirmed|secure_password',
            'blocked_code' => 'between:0,30',
            'blocked'      => 'between:0,1|numeric',
        ];
    }
}
