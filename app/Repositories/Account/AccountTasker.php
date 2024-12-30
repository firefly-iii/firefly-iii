<?php

/**
 * AccountTasker.php
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

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class AccountTasker.
 */
class AccountTasker implements AccountTaskerInterface
{
    private User $user;

    /**
     * @throws FireflyException
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $yesterday       = clone $start;
        $yesterday->subDay();
        $startSet        = app('steam')->finalAccountsBalance($accounts, $yesterday);
        $endSet          = app('steam')->finalAccountsBalance($accounts, $end);
        app('log')->debug('Start of accountreport');

        /** @var AccountRepositoryInterface $repository */
        $repository      = app(AccountRepositoryInterface::class);
        $defaultCurrency = app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);

        $return          = [
            'accounts' => [],
            'sums'     => [],
        ];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $id                                     = $account->id;
            $currency                               = $repository->getAccountCurrency($account) ?? $defaultCurrency;
            $return['sums'][$currency->id] ??= [
                'start'                   => '0',
                'end'                     => '0',
                'difference'              => '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_name'           => $currency->name,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $entry                                  = [
                'name'                    => $account->name,
                'id'                      => $account->id,
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_name'           => $currency->name,
                'currency_decimal_places' => $currency->decimal_places,
            ];

            // get first journal date:
            $first                                  = $repository->oldestJournal($account);
            $entry['start_balance']                 = $startSet[$account->id]['balance'] ?? '0';
            $entry['end_balance']                   = $endSet[$account->id]['balance'] ?? '0';

            // first journal exists, and is on start, then this is the actual opening balance:
            if (null !== $first && $first->date->isSameDay($start) && TransactionType::OPENING_BALANCE === $first->transactionType->type) {
                app('log')->debug(sprintf('Date of first journal for %s is %s', $account->name, $first->date->format('Y-m-d')));
                $entry['start_balance'] = $first->transactions()->where('account_id', $account->id)->first()->amount;
                app('log')->debug(sprintf('Account %s was opened on %s, so opening balance is %f', $account->name, $start->format('Y-m-d'), $entry['start_balance']));
            }
            $return['sums'][$currency->id]['start'] = bcadd($return['sums'][$currency->id]['start'], $entry['start_balance']);
            $return['sums'][$currency->id]['end']   = bcadd($return['sums'][$currency->id]['end'], $entry['end_balance']);
            $return['accounts'][$id]                = $entry;
        }

        foreach (array_keys($return['sums']) as $index) {
            $return['sums'][$index]['difference'] = bcsub($return['sums'][$index]['end'], $return['sums'][$index]['start']);
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    public function getExpenseReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setSourceAccounts($accounts)->setRange($start, $end);
        $collector->excludeDestinationAccounts($accounts);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])->withAccountInformation();
        $journals  = $collector->getExtractedJournals();

        $report    = $this->groupExpenseByDestination($journals);

        // sort the result
        // Obtain a list of columns
        $sum       = [];
        foreach ($report['accounts'] as $accountId => $row) {
            $sum[$accountId] = (float) $row['sum']; // intentional float
        }

        array_multisort($sum, SORT_ASC, $report['accounts']);

        return $report;
    }

    /**
     * @throws FireflyException
     */
    private function groupExpenseByDestination(array $array): array
    {
        $defaultCurrency = app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);

        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos   = app(CurrencyRepositoryInterface::class);
        $currencies      = [$defaultCurrency->id => $defaultCurrency];
        $report          = [
            'accounts' => [],
            'sums'     => [],
        ];

        /** @var array $journal */
        foreach ($array as $journal) {
            $sourceId                        = (int) $journal['destination_account_id'];
            $currencyId                      = (int) $journal['currency_id'];
            $key                             = sprintf('%s-%s', $sourceId, $currencyId);
            $currencies[$currencyId]  ??= $currencyRepos->find($currencyId);
            $report['accounts'][$key] ??= [
                'id'                      => $sourceId,
                'name'                    => $journal['destination_account_name'],
                'sum'                     => '0',
                'average'                 => '0',
                'count'                   => 0,
                'currency_id'             => $currencies[$currencyId]->id,
                'currency_name'           => $currencies[$currencyId]->name,
                'currency_symbol'         => $currencies[$currencyId]->symbol,
                'currency_code'           => $currencies[$currencyId]->code,
                'currency_decimal_places' => $currencies[$currencyId]->decimal_places,
            ];
            $report['accounts'][$key]['sum'] = bcadd($report['accounts'][$key]['sum'], $journal['amount']);

            app('log')->debug(sprintf('Sum for %s is now %s', $journal['destination_account_name'], $report['accounts'][$key]['sum']));

            ++$report['accounts'][$key]['count'];
        }

        // do averages and sums.
        foreach (array_keys($report['accounts']) as $key) {
            if ($report['accounts'][$key]['count'] > 1) {
                $report['accounts'][$key]['average'] = bcdiv($report['accounts'][$key]['sum'], (string) $report['accounts'][$key]['count']);
            }
            $currencyId                         = $report['accounts'][$key]['currency_id'];
            $report['sums'][$currencyId] ??= [
                'sum'                     => '0',
                'currency_id'             => $report['accounts'][$key]['currency_id'],
                'currency_name'           => $report['accounts'][$key]['currency_name'],
                'currency_symbol'         => $report['accounts'][$key]['currency_symbol'],
                'currency_code'           => $report['accounts'][$key]['currency_code'],
                'currency_decimal_places' => $report['accounts'][$key]['currency_decimal_places'],
            ];
            $report['sums'][$currencyId]['sum'] = bcadd($report['sums'][$currencyId]['sum'], $report['accounts'][$key]['sum']);
        }

        return $report;
    }

    /**
     * @throws FireflyException
     */
    public function getIncomeReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all incomes for the given accounts in the given period!
        // also transfers!
        // get all transactions:

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setDestinationAccounts($accounts)->setRange($start, $end);
        $collector->excludeSourceAccounts($accounts);
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])->withAccountInformation();
        $report    = $this->groupIncomeBySource($collector->getExtractedJournals());

        // sort the result
        // Obtain a list of columns
        $sum       = [];
        foreach ($report['accounts'] as $accountId => $row) {
            $sum[$accountId] = (float) $row['sum']; // intentional float
        }

        array_multisort($sum, SORT_DESC, $report['accounts']);

        return $report;
    }

    /**
     * @throws FireflyException
     */
    private function groupIncomeBySource(array $array): array
    {
        $defaultCurrency = app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);

        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos   = app(CurrencyRepositoryInterface::class);
        $currencies      = [$defaultCurrency->id => $defaultCurrency];
        $report          = [
            'accounts' => [],
            'sums'     => [],
        ];

        /** @var array $journal */
        foreach ($array as $journal) {
            $sourceId                        = (int) $journal['source_account_id'];
            $currencyId                      = (int) $journal['currency_id'];
            $key                             = sprintf('%s-%s', $sourceId, $currencyId);
            if (!array_key_exists($key, $report['accounts'])) {
                $currencies[$currencyId] ??= $currencyRepos->find($currencyId);
                $report['accounts'][$key] = [
                    'id'                      => $sourceId,
                    'name'                    => $journal['source_account_name'],
                    'sum'                     => '0',
                    'average'                 => '0',
                    'count'                   => 0,
                    'currency_id'             => $currencies[$currencyId]->id,
                    'currency_name'           => $currencies[$currencyId]->name,
                    'currency_symbol'         => $currencies[$currencyId]->symbol,
                    'currency_code'           => $currencies[$currencyId]->code,
                    'currency_decimal_places' => $currencies[$currencyId]->decimal_places,
                ];
            }
            $report['accounts'][$key]['sum'] = bcadd($report['accounts'][$key]['sum'], bcmul($journal['amount'], '-1'));
            ++$report['accounts'][$key]['count'];
        }

        // do averages and sums.
        foreach (array_keys($report['accounts']) as $key) {
            if ($report['accounts'][$key]['count'] > 1) {
                $report['accounts'][$key]['average'] = bcdiv($report['accounts'][$key]['sum'], (string) $report['accounts'][$key]['count']);
            }
            $currencyId                         = $report['accounts'][$key]['currency_id'];
            $report['sums'][$currencyId] ??= [
                'sum'                     => '0',
                'currency_id'             => $report['accounts'][$key]['currency_id'],
                'currency_name'           => $report['accounts'][$key]['currency_name'],
                'currency_symbol'         => $report['accounts'][$key]['currency_symbol'],
                'currency_code'           => $report['accounts'][$key]['currency_code'],
                'currency_decimal_places' => $report['accounts'][$key]['currency_decimal_places'],
            ];
            $report['sums'][$currencyId]['sum'] = bcadd($report['sums'][$currencyId]['sum'], $report['accounts'][$key]['sum']);
        }

        return $report;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }
}
