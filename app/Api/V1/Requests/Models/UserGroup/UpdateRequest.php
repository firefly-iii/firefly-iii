<?php

/*
 * UpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\UserGroup;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getData(): array
    {
        $fields = [
            'title'                => ['title', 'convertString'],
            'native_currency_id'   => ['native_currency_id', 'convertInteger'],
            'native_currency_code' => ['native_currency_code', 'convertString'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'title'                => ['required', 'min:1', 'max:255'],
            'native_currency_id'   => 'exists:transaction_currencies,id',
            'native_currency_code' => 'exists:transaction_currencies,code',
        ];
    }
}
