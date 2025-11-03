<?php

declare(strict_types=1);
/*
 * QueryRequest.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Validation\Validator;

class QueryRequest extends ApiRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function rules(): array
    {
        return [
            'query' => sprintf('min:0|max:50|%s', $this->required),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if ($validator->failed()) {
                    return;
                }
                $query = $this->convertString('query');
                $this->attributes->set('query', $query);
            }
        );
    }
}
