<?php

/*
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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Rules\IsBoolean;
use Illuminate\Contracts\Validation\Validator;

class ActiveObjectRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'active' => ['nullable', new IsBoolean()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $active = null;
            if($this->has('active')) {
                $active = $this->boolean('active', null);
            }

            $this->attributes->set('active', $active);
        });
    }
}
