<?php

/**
 * PiggyBankEventTransformer.php
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
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;

/**
 * Class PiggyBankEventTransformer
 */
class PiggyBankEventTransformer extends AbstractTransformer
{
    private TransactionCurrency $primaryCurrency;
    private bool                $convertToPrimary = false;

    /**
     * PiggyBankEventTransformer constructor.
     */
    public function __construct()
    {
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
    }

    /**
     * Convert piggy bank event.
     *
     * @throws FireflyException
     */
    public function transform(PiggyBankEvent $event): array
    {
        $currency      = $event->meta['currency'] ?? $this->primaryCurrency;
        $amount        = Steam::bcround($event->amount, $currency->decimal_places);
        $primaryAmount = null;
        if ($this->convertToPrimary && $currency->id === $this->primaryCurrency->id) {
            $primaryAmount = $amount;
        }
        if ($this->convertToPrimary && $currency->id !== $this->primaryCurrency->id) {
            $primaryAmount = Steam::bcround($event->native_amount, $this->primaryCurrency->decimal_places);
        }

        return [
            'id'                              => (string)$event->id,
            'created_at'                      => $event->created_at?->toAtomString(),
            'updated_at'                      => $event->updated_at?->toAtomString(),
            'amount'                          => $amount,
            'pc_amount'                       => $primaryAmount,

            // currencies according to 6.3.0
            'has_currency_setting'            => true,
            'currency_id'                     => (string)$currency->id,
            'currency_name'                   => $currency->name,
            'currency_code'                   => $currency->code,
            'currency_symbol'                 => $currency->symbol,
            'currency_decimal_places'         => $currency->decimal_places,

            'primary_currency_id'             => (string)$this->primaryCurrency->id,
            'primary_currency_name'           => $this->primaryCurrency->name,
            'primary_currency_code'           => $this->primaryCurrency->code,
            'primary_currency_symbol'         => $this->primaryCurrency->symbol,
            'primary_currency_decimal_places' => $this->primaryCurrency->decimal_places,

            'transaction_journal_id'          => null !== $event->transaction_journal_id ? (string)$event->transaction_journal_id : null,
            'transaction_group_id'            => $event->meta['transaction_group_id'],
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/piggy-banks/%d/events/%s', $event->piggy_bank_id, $event->id),
                ],
            ],
        ];
    }
}
