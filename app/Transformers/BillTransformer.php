<?php

/**
 * BillTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private readonly TransactionCurrency $primary;

    /**
     * BillTransformer constructor.
     */
    public function __construct()
    {
        $this->primary = Amount::getPrimaryCurrency();
    }

    /**
     * Transform the bill.
     */
    public function transform(Bill $bill): array
    {
        $currency = $bill->transactionCurrency;


        return [
            'id'                              => $bill->id,
            'created_at'                      => $bill->created_at->toAtomString(),
            'updated_at'                      => $bill->updated_at->toAtomString(),
            'name'                            => $bill->name,

            // currencies according to 6.3.0
            'object_has_currency_setting'     => true,
            'currency_id'                     => (string) $bill->transaction_currency_id,
            'currency_name'                   => $currency->name,
            'currency_code'                   => $currency->code,
            'currency_symbol'                 => $currency->symbol,
            'currency_decimal_places'         => $currency->decimal_places,

            'primary_currency_id'             => (string) $this->primary->id,
            'primary_currency_name'           => $this->primary->name,
            'primary_currency_code'           => $this->primary->code,
            'primary_currency_symbol'         => $this->primary->symbol,
            'primary_currency_decimal_places' => $this->primary->decimal_places,

            // amounts according to 6.3.0
            'amount_min'                      => $bill->amounts['amount_min'],
            'pc_amount_min'                   => $bill->amounts['pc_amount_min'],

            'amount_max'                      => $bill->amounts['amount_max'],
            'pc_amount_max'                   => $bill->amounts['pc_amount_max'],

            'amount_avg'                      => $bill->amounts['average'],
            'pc_amount_avg'                   => $bill->amounts['pc_average'],

            'date'                            => $bill->date->toAtomString(),
            'end_date'                        => $bill->end_date?->toAtomString(),
            'extension_date'                  => $bill->extension_date?->toAtomString(),
            'repeat_freq'                     => $bill->repeat_freq,
            'skip'                            => $bill->skip,
            'active'                          => $bill->active,
            'order'                           => $bill->order,
            'notes'                           => $bill->meta['notes'],
            'object_group_id'                 => $bill->meta['object_group_id'],
            'object_group_order'              => $bill->meta['object_group_order'],
            'object_group_title'              => $bill->meta['object_group_title'],

            'paid_dates'                      => $bill->meta['paid_dates'],
            'pay_dates'                       => $bill->meta['pay_dates'],
            'next_expected_match'             => $bill->meta['nem']?->toAtomString(),
            'next_expected_match_diff'        => $bill->meta['nem_diff'],

            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/'.$bill->id,
                ],
            ],
        ];
    }
}
