<?php

/**
 * SelectTransactionsRequest.php
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

use Illuminate\Contracts\Validation\Validator;
use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class SelectTransactionsRequest.
 */
class SelectTransactionsRequest extends FormRequest
{
    use ChecksLogin;

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'start'      => 'required|date|after:1900-01-01|before:2099-12-31|before:end|required_with:end',
            'end'        => 'required|date|after:1900-01-01|before:2099-12-31|after:start|required_with:start',
            'accounts'   => 'required',
            'accounts.*' => 'required|exists:accounts,id|belongsToUser:accounts',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
