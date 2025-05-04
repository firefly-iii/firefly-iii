<?php

/**
 * UserFormRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class UserFormRequest.
 */
class UserFormRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get data for controller.
     */
    public function getUserData(): array
    {
        return [
            'email'        => $this->convertString('email'),
            'blocked'      => 1 === $this->convertInteger('blocked'),
            'blocked_code' => $this->convertString('blocked_code'),
            'password'     => $this->convertString('password'),
            'is_owner'     => 1 === $this->convertInteger('is_owner'),
        ];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'id'           => 'required|exists:users,id',
            'email'        => 'email|required',
            'password'     => 'confirmed|secure_password',
            'blocked_code' => 'min:0|max:32|nullable',
            'blocked'      => 'min:0|max:1|numeric',
            'is_owner'     => 'min:0|max:1|numeric',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
