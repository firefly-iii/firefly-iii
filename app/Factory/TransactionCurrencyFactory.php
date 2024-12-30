<?php

/**
 * TransactionCurrencyFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\QueryException;

/**
 * Class TransactionCurrencyFactory
 */
class TransactionCurrencyFactory
{
    /**
     * @throws FireflyException
     */
    public function create(array $data): TransactionCurrency
    {
        $data['code']           = e($data['code']);
        $data['symbol']         = e($data['symbol']);
        $data['name']           = e($data['name']);
        $data['decimal_places'] = (int) $data['decimal_places'];
        // if the code already exists (deleted)
        // force delete it and then create the transaction:
        $count                  = TransactionCurrency::withTrashed()->whereCode($data['code'])->count();
        if (1 === $count) {
            $old = TransactionCurrency::withTrashed()->whereCode($data['code'])->first();
            $old->forceDelete();
            app('log')->warning(sprintf('Force deleted old currency with ID #%d and code "%s".', $old->id, $data['code']));
        }

        try {
            /** @var TransactionCurrency $result */
            $result = TransactionCurrency::create(
                [
                    'name'           => $data['name'],
                    'code'           => $data['code'],
                    'symbol'         => $data['symbol'],
                    'decimal_places' => $data['decimal_places'],
                    'enabled'        => false,
                ]
            );
        } catch (QueryException $e) {
            $result = null;
            app('log')->error(sprintf('Could not create new currency: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());

            throw new FireflyException('400004: Could not store new currency.', 0, $e);
        }

        return $result;
    }

    public function find(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        $currencyCode = e($currencyCode);
        $currencyId   = (int) $currencyId;

        if ('' === $currencyCode && 0 === $currencyId) {
            app('log')->debug('Cannot find anything on empty currency code and empty currency ID!');

            return null;
        }

        // first by ID:
        if ($currencyId > 0) {
            $currency = TransactionCurrency::find($currencyId);
            if (null !== $currency) {
                return $currency;
            }
            app('log')->warning(sprintf('Currency ID is %d but found nothing!', $currencyId));
        }
        // then by code:
        if ('' !== $currencyCode) {
            $currency = TransactionCurrency::whereCode($currencyCode)->first();
            if (null !== $currency) {
                return $currency;
            }
            app('log')->warning(sprintf('Currency code is %d but found nothing!', $currencyCode));
        }
        app('log')->warning('Found nothing for currency.');

        return null;
    }
}
