<?php

/*
 * StoreRequest.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Request\UserGroup;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    protected array $acceptedRoles = [UserRoleEnum::OWNER, UserRoleEnum::FULL];

    public function getAll(): array
    {
        return [
            'title' => $this->convertString('title'),
        ];
    }

    public function rules(): array
    {
        $roles = [];
        foreach(UserRoleEnum::cases() as $role) {
            $roles[] = $role->value;
        }
        $string = implode(',', $roles);

        return [
            'title'                => 'unique:user_groups,title|required|min:1|max:255',
            'members'              => 'required|min:1',
            'members.*.user_email' => 'email|missing_with:members.*.user_id',
            'members.*.user_id' => 'integer|exists:users,id|missing_with:members.*.user_email',
            'members.*.roles'   => 'required|array|min:1',
            'members.*.roles.*' => sprintf('required|in:%s',$string),
        ];
    }
}
