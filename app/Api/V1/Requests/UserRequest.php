<?php

/**
 * UserRequest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\User;


/**
 * Class UserRequest
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class UserRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $result = false;
        // Only allow authenticated users
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();

            /** @var UserRepositoryInterface $repository */
            $repository = app(UserRepositoryInterface::class);

            if ($repository->hasRole($user, 'owner')) {
                $result = true; // @codeCoverageIgnore
            }
        }

        return $result;
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $blocked = false;
        if (null === $this->get('blocked')) {
            $blocked = $this->boolean('blocked');
        }
        $data = [
            'email'        => $this->string('email'),
            'blocked'      => $blocked,
            'blocked_code' => $this->string('blocked_code'),
            'role'         => $this->string('role'),
        ];

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'email'        => 'required|email|unique:users,email,',
            'blocked'      => [new IsBoolean],
            'blocked_code' => 'in:email_changed',
            'role'         => 'in:owner,demo',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $user           = $this->route()->parameter('user');
                $rules['email'] = 'required|email|unique:users,email,' . $user->id;
                break;
        }

        return $rules;
    }

}
