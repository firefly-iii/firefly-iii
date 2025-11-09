<?php

/*
 * UserUpdateRequest.php
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

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class UserUpdateRequest
 */
class UserUpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Logged in + owner
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $blocked = false;
        if (null !== $this->get('blocked')) {
            $blocked = $this->boolean('blocked');
        }

        return [
            'email'        => $this->convertString('email'),
            'blocked'      => $blocked,
            'blocked_code' => $this->convertString('blocked_code'),
            'role'         => $this->convertString('role'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route()->parameter('user');

        return [
            'email'        => sprintf('email|unique:users,email,%d', $user->id),
            'blocked'      => [new IsBoolean()],
            'blocked_code' => 'in:email_changed',
            'role'         => 'in:owner,demo,',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        /** @var null|User $current */
        $current = $this->route()->parameter('user');
        $validator->after(
            static function (Validator $validator) use ($current): void {
                $isAdmin = auth()->user()->hasRole('owner');
                // not admin, and not own user?
                if (auth()->check() && false === $isAdmin && $current?->id !== auth()->user()->id) {
                    $validator->errors()->add('email', (string) trans('validation.invalid_selection'));
                }
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
