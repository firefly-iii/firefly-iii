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


use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;

/**
 * Class PiggyBankEventTransformer
 */
class PiggyBankEventTransformer extends AbstractTransformer
{
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepos;
    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * PiggyBankEventTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository    = app(AccountRepositoryInterface::class);
        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        $this->piggyRepos    = app(PiggyBankRepositoryInterface::class);
    }

    /**
     * Convert piggy bank event.
     *
     * @param PiggyBankEvent $event
     *
     * @return array
     */
    public function transform(PiggyBankEvent $event): array
    {
        // get account linked to piggy bank
        $account = $event->piggyBank->account;

        // set up repositories.
        $this->repository->setUser($account->user);
        $this->currencyRepos->setUser($account->user);
        $this->piggyRepos->setUser($account->user);

        // get associated currency or fall back to the default:
        $currency = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrencyByUser($account->user);

        // get associated journal and transaction, if any:
        $journalId = $event->transaction_journal_id;
        $groupId   = null;
        if (0 !== (int) $journalId) {
            $groupId   = (int) $event->transactionJournal->transaction_group_id;
            $journalId = (int) $journalId;
        }
        return [
            'id'                      => (int) $event->id,
            'created_at'              => $event->created_at->toAtomString(),
            'updated_at'              => $event->updated_at->toAtomString(),
            'amount'                  => number_format((float) $event->amount, $currency->decimal_places, '.', ''),
            'currency_id'             => (int) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => (int) $currency->decimal_places,
            'transaction_journal_id'  => $journalId,
            'transaction_group_id'    => $groupId,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/piggy_bank_events/' . $event->id,
                ],
            ],
        ];
    }

}
