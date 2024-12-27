<?php

/*
 * ValidatesBulkTransactionQuery.php
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

namespace FireflyIII\Validation\Api\Data\Bulk;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Validation\Validator;

trait ValidatesBulkTransactionQuery
{
    protected function validateTransactionQuery(Validator $validator): void
    {
        $data = $validator->getData();
        // assumption is all validation has already taken place and the query key exists.
        $json = json_decode($data['query'], true, 8, JSON_THROW_ON_ERROR);

        if (array_key_exists('account_id', $json['where'])
            && array_key_exists('account_id', $json['update'])
        ) {
            // find both accounts, must be same type.
            // already validated: belongs to this user.
            $repository     = app(AccountRepositoryInterface::class);
            $source         = $repository->find((int) $json['where']['account_id']);
            $dest           = $repository->find((int) $json['update']['account_id']);
            if (null === $source) {
                $validator->errors()->add('query', sprintf((string) trans('validation.invalid_query_data'), 'where', 'account_id'));

                return;
            }
            if (null === $dest) {
                $validator->errors()->add('query', sprintf((string) trans('validation.invalid_query_data'), 'update', 'account_id'));

                return;
            }
            if ($source->accountType->type !== $dest->accountType->type) {
                $validator->errors()->add('query', (string) trans('validation.invalid_query_account_type'));

                return;
            }

            // must have same currency:
            // some account types (like expenses) do not have currency, so they have to be omitted
            $sourceCurrency = $repository->getAccountCurrency($source);
            $destCurrency   = $repository->getAccountCurrency($dest);
            if (
                null !== $sourceCurrency
                && null !== $destCurrency
                && $sourceCurrency->id !== $destCurrency->id
            ) {
                $validator->errors()->add('query', (string) trans('validation.invalid_query_currency'));
            }
        }
    }
}
