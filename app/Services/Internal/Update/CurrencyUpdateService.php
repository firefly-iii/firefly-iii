<?php

/**
 * CurrencyUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\TransactionCurrency;

/**
 * Class CurrencyUpdateService
 */
class CurrencyUpdateService
{
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency
    {
        if (array_key_exists('code', $data) && '' !== (string) $data['code']) {
            $currency->code = e($data['code']);
        }

        if (array_key_exists('symbol', $data) && '' !== (string) $data['symbol']) {
            $currency->symbol = e($data['symbol']);
        }

        if (array_key_exists('name', $data) && '' !== (string) $data['name']) {
            $currency->name = e($data['name']);
        }

        $currency->enabled          = false;

        if (array_key_exists('decimal_places', $data) && is_int($data['decimal_places'])) {
            $currency->decimal_places = $data['decimal_places'];
        }
        $currency->userGroupEnabled = null;
        $currency->userGroupDefault = null;
        $currency->save();

        return $currency;
    }
}
