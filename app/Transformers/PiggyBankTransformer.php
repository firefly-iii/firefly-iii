<?php

/**
 * PiggyBankTransformer.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;

/**
 * Class PiggyBankTransformer
 */
class PiggyBankTransformer extends AbstractTransformer
{
    private TransactionCurrency                   $primaryCurrency;

    /**
     * PiggyBankTransformer constructor.
     */
    public function __construct()
    {
        $this->primaryCurrency = Amount::getPrimaryCurrency();
    }

    /**
     * Transform the piggy bank.
     *
     * @throws FireflyException
     */
    public function transform(PiggyBank $piggyBank): array
    {
        // Amounts, depending on 0.0 state of target amount
        $percentage = null;
        if (null !== $piggyBank->meta['target_amount'] && 0 !== bccomp($piggyBank->meta['current_amount'], '0')) { // target amount is not 0.00
            $percentage = (int)bcmul(bcdiv($piggyBank->meta['current_amount'], $piggyBank->meta['target_amount']), '100');
        }
        $startDate  = $piggyBank->start_date?->toAtomString();
        $targetDate = $piggyBank->target_date?->toAtomString();

        return [
            'id'                              => (string)$piggyBank->id,
            'created_at'                      => $piggyBank->created_at->toAtomString(),
            'updated_at'                      => $piggyBank->updated_at->toAtomString(),
            'name'                            => $piggyBank->name,
            'percentage'                      => $percentage,
            'start_date'                      => $startDate,
            'target_date'                     => $targetDate,
            'order'                           => $piggyBank->order,
            'active'                          => true,
            'notes'                           => $piggyBank->meta['notes'],
            'object_group_id'                 => $piggyBank->meta['object_group_id'],
            'object_group_order'              => $piggyBank->meta['object_group_order'],
            'object_group_title'              => $piggyBank->meta['object_group_title'],
            'accounts'                        => $piggyBank->meta['accounts'],

            // currency settings, 6.3.0.
            'object_has_currency_setting'     => true,
            'currency_id'                     => (string)$piggyBank->meta['currency']->id,
            'currency_name'                   => $piggyBank->meta['currency']->name,
            'currency_code'                   => $piggyBank->meta['currency']->code,
            'currency_symbol'                 => $piggyBank->meta['currency']->symbol,
            'currency_decimal_places'         => $piggyBank->meta['currency']->decimal_places,

            'primary_currency_id'             => (string)$this->primaryCurrency->id,
            'primary_currency_name'           => $this->primaryCurrency->name,
            'primary_currency_code'           => $this->primaryCurrency->code,
            'primary_currency_symbol'         => $this->primaryCurrency->symbol,
            'primary_currency_decimal_places' => (int)$this->primaryCurrency->decimal_places,


            'target_amount'                   => $piggyBank->meta['target_amount'],
            'pc_target_amount'                => $piggyBank->meta['pc_target_amount'],
            'current_amount'                  => $piggyBank->meta['current_amount'],
            'pc_current_amount'               => $piggyBank->meta['pc_current_amount'],
            'left_to_save'                    => $piggyBank->meta['left_to_save'],
            'pc_left_to_save'                 => $piggyBank->meta['pc_left_to_save'],
            'save_per_month'                  => $piggyBank->meta['save_per_month'],
            'pc_save_per_month'               => $piggyBank->meta['pc_save_per_month'],

            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/piggy-banks/%d', $piggyBank->id),
                ],
            ],
        ];
    }

    private function renderAccounts(PiggyBank $piggyBank): array
    {
        $return = [];
        foreach ($piggyBank->accounts()->get() as $account) {
            $return[] = [
                'id'                => (string)$account->id,
                'name'              => $account->name,
                'current_amount'    => (string)$account->pivot->current_amount,
                'pc_current_amount' => (string)$account->pivot->native_current_amount,
                // TODO add balance, add left to save.
            ];
        }

        return $return;
    }
}
