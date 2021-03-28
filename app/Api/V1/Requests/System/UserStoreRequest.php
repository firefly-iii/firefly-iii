<?php

/*
 * UserStoreRequest.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\System;

use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UserStoreRequest
 */
class UserStoreRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Logged in + owner
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('owner');
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $blocked = false;
        if (null !== $this->get('blocked')) {
            $blocked = $this->boolean('blocked');
        }

        return [
            'email'        => $this->string('email'),
            'blocked'      => $blocked,
            'blocked_code' => $this->string('blocked_code'),
            'role'         => $this->string('role'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email'        => 'required|email|unique:users,email',
            'blocked'      => [new IsBoolean],
            'blocked_code' => 'in:email_changed',
            'role'         => 'in:owner,demo',
        ];
    }

}
