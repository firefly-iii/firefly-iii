<?php

/*
 * QueryRequest.php
 * Copyright (c) 2026 james@firefly-iii.org
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
use FireflyIII\Rules\IsValidFilterInstruction;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Contracts\Validation\Validator;

class FilterRequest extends ApiRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    private ?string $filterClass = null;

    public function handleConfig(array $config): void
    {
        parent::handleConfig($config);

        $this->filterClass = $config['filter_class'] ?? null;

        if (null === $this->filterClass) {
            // throw new RuntimeException('FilterRequest requires a filter_class config');
            $this->filterClass = 'Account';
        }
    }

    public function rules(): array
    {
        return [
            'filter' => ['min:0', 'max:255', $this->required, new IsValidFilterInstruction((string) $this->filterClass)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (count($validator->failed()) > 0) {
                return;
            }
        });
    }

    //    private function convertToFilter(string $value): array {
    //        return [];
    //    }
}
