<?php

/*
 * AccountTransformer.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Transformers\V2;

use FireflyIII\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;

/**
 * Class AccountTransformer
 */
class ExchangeRateTransformer extends AbstractTransformer
{


    /**
     * This method collects meta-data for one or all accounts in the transformer's collection.
     */
    public function collectMetaData(Collection $objects): Collection
    {
        return $objects;
    }

    /**
     * Transform the account.
     */
    public function transform(CurrencyExchangeRate $rate): array
    {
        return [
            'id'         => (string) $rate->id,
            'created_at' => $rate->created_at->toAtomString(),
            'updated_at' => $rate->updated_at->toAtomString(),

            'from_currency_id'             => (string) $rate->fromCurrency->id,
            'from_currency_code'           => $rate->fromCurrency->code,
            'from_currency_symbol'         => $rate->fromCurrency->symbol,
            'from_currency_decimal_places' => $rate->fromCurrency->decimal_places,

            'to_currency_id'             => (string) $rate->toCurrency->id,
            'to_currency_code'           => $rate->toCurrency->code,
            'to_currency_symbol'         => $rate->toCurrency->symbol,
            'to_currency_decimal_places' => $rate->toCurrency->decimal_places,

            'rate'  => $rate->rate,
            'date'  => $rate->date->toAtomString(),
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/exchange-rates/%s', $rate->id),
                ],
            ],
        ];
    }
}
