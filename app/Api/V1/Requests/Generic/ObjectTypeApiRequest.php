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

namespace FireflyIII\Api\V1\Requests\Generic;

use FireflyIII\Api\V1\Requests\ApiRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Rules\Account\IsValidAccountTypeList;
use FireflyIII\Rules\TransactionType\IsValidTransactionTypeList;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use Illuminate\Validation\Validator;
use RuntimeException;

class ObjectTypeApiRequest extends ApiRequest
{
    use AccountFilter;
    use TransactionFilter;

    private ?string $objectType = null;

    public function handleConfig(array $config): void
    {
        parent::handleConfig($config);

        $this->objectType = $config['object_type'] ?? null;

        if (!$this->objectType) {
            throw new RuntimeException('ObjectTypeApiRequest requires a object_type config');
        }
    }

    public function rules(): array
    {
        $rule  = null;
        if (Account::class === $this->objectType) {
            $rule = new IsValidAccountTypeList();
        }
        if (Transaction::class === $this->objectType) {
            $rule = new IsValidTransactionTypeList();
        }
        $rules = [
            'types' => [$rule],
        ];
        if ('' !== $this->required) {
            $rules['types'][] = $this->required;
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if ($validator->failed()) {
                    return;
                }
                $type = $this->convertString('types', 'all');
                $this->attributes->set('type', $type);

                switch ($this->objectType) {
                    default:
                        $this->attributes->set('types', []);

                        // no break
                    case Account::class:
                        $this->attributes->set('types', $this->mapAccountTypes($type));

                        break;

                    case Transaction::class:
                        $this->attributes->set('types', $this->mapTransactionTypes($type));

                        break;
                }
            }
        );
    }
}
