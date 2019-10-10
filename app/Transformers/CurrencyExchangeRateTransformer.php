<?php
/**
 * CurrencyExchangeRateTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Transformers;


use FireflyIII\Models\CurrencyExchangeRate;
use Log;

/**
 * Class CurrencyExchangeRateTransformer
 */
class CurrencyExchangeRateTransformer extends AbstractTransformer
{

    /**
     * PiggyBankEventTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param CurrencyExchangeRate $rate
     *
     * @return array
     */
    public function transform(CurrencyExchangeRate $rate): array
    {
        $result = round((float)$rate->rate * (float)$this->parameters->get('amount'), $rate->toCurrency->decimal_places);
        $result = 0.0 === $result ? null : $result;
        $data   = [
            'id'                           => (int)$rate->id,
            'created_at'                   => $rate->created_at->toAtomString(),
            'updated_at'                   => $rate->updated_at->toAtomString(),
            'from_currency_id'             => $rate->fromCurrency->id,
            'from_currency_name'           => $rate->fromCurrency->name,
            'from_currency_code'           => $rate->fromCurrency->code,
            'from_currency_symbol'         => $rate->fromCurrency->symbol,
            'from_currency_decimal_places' => $rate->fromCurrency->decimal_places,
            'to_currency_id'               => $rate->toCurrency->id,
            'to_currency_name'             => $rate->toCurrency->name,
            'to_currency_code'             => $rate->toCurrency->code,
            'to_currency_symbol'           => $rate->toCurrency->symbol,
            'to_currency_decimal_places'   => $rate->toCurrency->decimal_places,
            'date'                         => $rate->date->format('Y-m-d'),
            'rate'                         => (float)$rate->rate,
            'amount'                       => $result,
            'links'                        => [
                [
                    'rel' => 'self',
                    'uri' => '/currency_exchange_rates/' . $rate->id,
                ],
            ],
        ];

        return $data;
    }
}
