<?php

declare(strict_types=1);
/*
 * AccountTypeApiRequest.php
 * Copyright (c) 2025 https://github.com/ctrl-f5
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

namespace FireflyIII\Api\V1\Requests\Models\Account;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Support\Http\Api\AccountFilter;
use Illuminate\Validation\Validator;

class AccountTypeApiRequest extends ApiRequest
{
    use AccountFilter;

    public function rules(): array
    {
        return [
            'type'  => sprintf('in:%s', implode(',', array_keys($this->types))),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if (!$validator->valid()) {
                    return;
                }

                $type = $this->convertString('type', 'all');
                $this->attributes->add([
                    'type' => $type,
                    'types' => $this->mapAccountTypes($type),
                ]);
            }
        );
    }
}
