<?php
/**
 * AccountTasker.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountTasker.
 */
class AccountTasker implements AccountTaskerInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $yesterday = clone $start;
        $yesterday->subDay();
        $startSet = app('steam')->balancesByAccounts($accounts, $yesterday);
        $endSet   = app('steam')->balancesByAccounts($accounts, $end);

        Log::debug('Start of accountreport');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        $defaultCurrency    = app('amount')->getDefaultCurrencyByUser($this->user);

        $return = [
            'currencies' => [],
            'start'      => '0',
            'end'        => '0',
            'difference' => '0',
            'accounts'   => [],
        ];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $id                     = $account->id;
            $currencyId             = (int)$repository->getMetaValue($account, 'currency_id');
            $currency               = $currencyRepository->findNull($currencyId);
            $return['currencies'][] = $currencyId;
            $entry                  = [
                'name'          => $account->name,
                'id'            => $account->id,
                'currency'      => $currency ?? $defaultCurrency,
                'start_balance' => '0',
                'end_balance'   => '0',
            ];

            // get first journal date:
            $first                  = $repository->oldestJournal($account);
            $entry['start_balance'] = $startSet[$account->id] ?? '0';
            $entry['end_balance']   = $endSet[$account->id] ?? '0';

            // first journal exists, and is on start, then this is the actual opening balance:
            if (null !== $first && $first->date->isSameDay($start)) {
                Log::debug(sprintf('Date of first journal for %s is %s', $account->name, $first->date->format('Y-m-d')));
                $entry['start_balance'] = $first->transactions()->where('account_id', $account->id)->first()->amount;
                Log::debug(sprintf('Account %s was opened on %s, so opening balance is %f', $account->name, $start->format('Y-m-d'), $entry['start_balance']));
            }
            $return['start'] = bcadd($return['start'], $entry['start_balance']);
            $return['end']   = bcadd($return['end'], $entry['end_balance']);

            $return['accounts'][$id] = $entry;
        }
        $return['currencies'] = count(array_unique($return['currencies']));
        $return['difference'] = bcsub($return['end'], $return['start']);

        return $return;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getExpenseReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withAccountInformation();
        $journals = $collector->getExtractedJournals();
        $expenses = $this->groupByDestination($journals);

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($expenses as $accountId => $row) {
            $sum[$accountId] = (float)$row['sum'];
        }

        array_multisort($sum, SORT_ASC, $expenses);

        return $expenses;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getIncomeReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withAccountInformation();
        $income = $this->groupByDestination($collector->getExtractedJournals());

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($income as $accountId => $row) {
            $sum[$accountId] = (float)$row['sum'];
        }

        array_multisort($sum, SORT_DESC, $income);

        return $income;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $array
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function groupByDestination(array $array): array
    {
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser($this->user);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);
        $currencies    = [$defaultCurrency->id => $defaultCurrency,];
        $expenses      = [];
        $countAccounts = []; // if count remains 0 use original name, not the name with the currency.


        /** @var array $journal */
        foreach ($array as $journal) {
            $opposingId                 = (int)$journal['destination_account_id'];
            $currencyId                 = (int)$journal['currency_id'];
            $key                        = sprintf('%s-%s', $opposingId, $currencyId);
            $name                       = sprintf('%s (%s)', $journal['destination_account_name'], $journal['currency_name']);
            $countAccounts[$opposingId] = isset($countAccounts[$opposingId]) ? $countAccounts[$opposingId] + 1 : 1;
            if (!isset($expenses[$key])) {
                $currencies[$currencyId] = $currencies[$currencyId] ?? $currencyRepos->findNull($currencyId);
                $expenses[$key]          = [
                    'id'              => $opposingId,
                    'name'            => $name,
                    'original'        => $journal['destination_account_name'],
                    'sum'             => '0',
                    'average'         => '0',
                    'currencies'      => [],
                    'single_currency' => $currencies[$currencyId],
                    'count'           => 0,
                ];
            }
            $expenses[$key]['currencies'][] = (int)$journal['currency_id'];
            $expenses[$key]['sum']          = bcadd($expenses[$key]['sum'], $journal['amount']);
            ++$expenses[$key]['count'];
        }
        // do averages:
        $keys = array_keys($expenses);
        foreach ($keys as $key) {
            $opposingId = $expenses[$key]['id'];
            if (1 === $countAccounts[$opposingId]) {
                $expenses[$key]['name'] = $expenses[$key]['original'];
            }

            if ($expenses[$key]['count'] > 1) {
                $expenses[$key]['average'] = bcdiv($expenses[$key]['sum'], (string)$expenses[$key]['count']);
            }
            $expenses[$key]['currencies']     = count(array_unique($expenses[$key]['currencies']));
            $expenses[$key]['all_currencies'] = count($currencies);
        }

        return $expenses;
    }
}
