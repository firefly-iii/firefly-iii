<?php
/**
 * UserFormRequest.php
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
 * Class UserFormRequest.
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
            'blocked'      => 1 === $this->integer('blocked'),
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
            'blocked_code' => 'between:0,30|nullable',
            'blocked'      => 'between:0,1|numeric',
        ];
    }
}
