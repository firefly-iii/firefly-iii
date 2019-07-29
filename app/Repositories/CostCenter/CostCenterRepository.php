<?php
/**
 * CostCenterRepository.php
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

namespace FireflyIII\Repositories\CostCenter;

use Carbon\Carbon;
use FireflyIII\Factory\CostCenterFactory;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\CostCenter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\CostCenterDestroyService;
use FireflyIII\Services\Internal\Update\CostCenterUpdateService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class CostCenterRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CostCenterRepository implements CostCenterRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param CostCenter $costCenter
     *
     * @return bool
     *

     */
    public function destroy(CostCenter $costCenter): bool
    {
        /** @var CostCenterDestroyService $service */
        $service = app(CostCenterDestroyService::class);
        $service->destroy($costCenter);

        return true;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): string
    {
        $set = $this->earnedInPeriodCollection($costCenters, $accounts, $start, $end);

        return (string)$set->sum('transaction_amount');
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function earnedInPeriodCollection(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        if (0 !== $accounts->count()) {
            $collector->setAccounts($accounts);
        }

        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setCostCenters($costCenters);

        return $collector->getTransactions();
    }

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount earned in this period, grouped per currency, where no center cost was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPcWoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->withoutCostCenter();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === 1) {
                    return $transaction;
                }

                return null;
            }
        );

        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId = $transaction->transaction_currency_id;
            if (!isset($return[$currencyId])) {
                $return[$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $transaction->transaction_currency_symbol,
                    'currency_code'           => $transaction->transaction_currency_code,
                    'currency_decimal_places' => $transaction->transaction_currency_dp,
                ];
            }
            $return[$currencyId]['spent'] = bcadd($return[$currencyId]['spent'], $transaction->transaction_amount);
        }

        return $return;
    }

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPerCurrency(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);

        if ($costCenters->count() > 0) {
            $collector->setCostCenters($costCenters);
        }
        if (0 === $costCenters->count()) {
            $collector->setCostCenters($this->getCostCenters());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set    = $collector->getTransactions();
        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $jrnlCatId  = (int)$transaction->transaction_journal_cost_center_id;
            $transCatId = (int)$transaction->transaction_cost_center_id;
            $costCenter = max($jrnlCatId, $transCatId);
            $currencyId = (int)$transaction->transaction_currency_id;
            $name       = $transaction->transaction_cost_center_name;
            $name       = '' === (string)$name ? $transaction->transaction_journal_cost_center_name : $name;
            // make array for cost center:
            if (!isset($return[$costCenter])) {
                $return[$costCenter] = [
                    'name'   => $name,
                    'earned' => [],
                ];
            }
            if (!isset($return[$costCenter]['earned'][$currencyId])) {
                $return[$costCenter]['earned'][$currencyId] = [
                    'earned'                  => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $transaction->transaction_currency_symbol,
                    'currency_code'           => $transaction->transaction_currency_code,
                    'currency_decimal_places' => $transaction->transaction_currency_dp,
                ];
            }
            $return[$costCenter]['earned'][$currencyId]['earned']
                = bcadd($return[$costCenter]['earned'][$currencyId]['earned'], $transaction->transaction_amount);
        }

        return $return;
    }

    /**
     * Find a cost center.
     *
     * @param string $name
     *
     * @return CostCenter|null
     */
    public function findByName(string $name): ?CostCenter
    {
        $costCenters = $this->user->costCenters()->get(['cost_centers.*']);
        foreach ($costCenters as $costCenter) {
            if ($costCenter->name === $name) {
                return $costCenter;
            }
        }

        return null;
    }

    /**
     * Find a cost center or return NULL
     *
     * @param int $costCenter
     *
     * @return CostCenter|null
     */
    public function findNull(int $costCenter): ?CostCenter
    {
        return $this->user->costCenters()->find($costCenter);
    }

    /**
     * @param CostCenter $costCenter
     *
     * @return Carbon|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function firstUseDate(CostCenter $costCenter): ?Carbon
    {
        $firstJournalDate     = $this->getFirstJournalDate($costCenter);
        $firstTransactionDate = $this->getFirstTransactionDate($costCenter);

        if (null === $firstTransactionDate && null === $firstJournalDate) {
            return null;
        }
        if (null === $firstTransactionDate) {
            return $firstJournalDate;
        }
        if (null === $firstJournalDate) {
            return $firstTransactionDate;
        }

        if ($firstTransactionDate < $firstJournalDate) {
            return $firstTransactionDate;
        }

        return $firstJournalDate;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Get all cost centers with ID's.
     *
     * @param array $costCenter
     *
     * @return Collection
     */
    public function getByIds(array $costCenter): Collection
    {
        return $this->user->costCenters()->whereIn('id', $costCenter)->get();
    }

    /**
     * Returns a list of all the cost centers belonging to a user.
     *
     * @return Collection
     */
    public function getCostCenters(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->costCenters()->orderBy('name', 'ASC')->get();
        $set = $set->sortBy(
            function (CostCenter $costCenter) {
                return strtolower($costCenter->name);
            }
        );

        return $set;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param CostCenter   $costCenter
     * @param Collection $accounts
     *
     * @return Carbon|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function lastUseDate(CostCenter $costCenter, Collection $accounts): ?Carbon
    {
        $lastJournalDate     = $this->getLastJournalDate($costCenter, $accounts);
        $lastTransactionDate = $this->getLastTransactionDate($costCenter, $accounts);

        if (null === $lastTransactionDate && null === $lastJournalDate) {
            return null;
        }
        if (null === $lastTransactionDate) {
            return $lastJournalDate;
        }
        if (null === $lastJournalDate) {
            return $lastTransactionDate;
        }

        if ($lastTransactionDate > $lastJournalDate) {
            return $lastTransactionDate;
        }

        return $lastJournalDate;
    }

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var CostCenter $costCenter */
        foreach ($costCenters as $costCenter) {
            $data[$costCenter->id] = [
                'name'    => $costCenter->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCostCenters($costCenters)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getTransactions();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (1 === bccomp($transaction->transaction_amount, '0')) {
                continue;
            }
            $costCenter                          = max((int)$transaction->transaction_journal_cost_center_id, (int)$transaction->transaction_cost_center_id);
            $date                                = $transaction->date->format($carbonFormat);
            $data[$costCenter]['entries'][$date] = bcadd($data[$costCenter]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpensesNoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        $collector->withoutCostCenter();
        $transactions = $collector->getTransactions();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_cost_center'),
            'sum'     => '0',
        ];

        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (1 === bccomp($transaction->transaction_amount, '0')) {
                continue;
            }
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var CostCenter $costCenter */
        foreach ($costCenters as $costCenter) {
            $data[$costCenter->id] = [
                'name'    => $costCenter->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCostCenters($costCenters)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getTransactions();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if negative, skip:
            if (bccomp($transaction->transaction_amount, '0') === -1) {
                continue;
            }
            $costCenter                          = max((int)$transaction->transaction_journal_cost_center_id, (int)$transaction->transaction_cost_center_id);
            $date                                = $transaction->date->format($carbonFormat);
            $data[$costCenter]['entries'][$date] = bcadd($data[$costCenter]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncomeNoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array
    {
        Log::debug('Now in periodIncomeNoCostCenter()');
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->withoutCostCenter();
        $transactions = $collector->getTransactions();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_cost_center'),
            'sum'     => '0',
        ];
        Log::debug('Looping transactions..');
        foreach ($transactions as $transaction) {
            // if negative, skip:
            if (bccomp($transaction->transaction_amount, '0') === -1) {
                continue;
            }
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }
        Log::debug('Done looping transactions..');
        Log::debug('Finished periodIncomeNoCostCenter()');

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchCostCenter(string $query): Collection
    {
        $query = sprintf('%%%s%%', $query);

        return $this->user->costCenters()->where('name', 'LIKE', $query)->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): string
    {
        $set = $this->spentInPeriodCollection($costCenters, $accounts, $start, $end);


        return (string)$set->sum('transaction_amount');
    }

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function spentInPeriodCollection(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCostCenters($costCenters);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        return $collector->getTransactions();
    }

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no cost center was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPcWoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCostCenter();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId = $transaction->transaction_currency_id;
            if (!isset($return[$currencyId])) {
                $return[$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $transaction->transaction_currency_symbol,
                    'currency_code'           => $transaction->transaction_currency_code,
                    'currency_decimal_places' => $transaction->transaction_currency_dp,
                ];
            }
            $return[$currencyId]['spent'] = bcadd($return[$currencyId]['spent'], $transaction->transaction_amount);
        }

        return $return;
    }

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);

        if ($costCenters->count() > 0) {
            $collector->setCostCenters($costCenters);
        }
        if (0 === $costCenters->count()) {
            $collector->setCostCenters($this->getCostCenters());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set    = $collector->getTransactions();
        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $jrnlCatId  = (int)$transaction->transaction_journal_cost_center_id;
            $transCatId = (int)$transaction->transaction_cost_center_id;
            $costCenter = max($jrnlCatId, $transCatId);
            $currencyId = (int)$transaction->transaction_currency_id;
            $name       = $transaction->transaction_cost_center_name;
            $name       = '' === (string)$name ? $transaction->transaction_journal_cost_center_name : $name;

            // make array for cost center:
            if (!isset($return[$costCenter])) {
                $return[$costCenter] = [
                    'name'  => $name,
                    'spent' => [],
                ];
            }
            if (!isset($return[$costCenter]['spent'][$currencyId])) {
                $return[$costCenter]['spent'][$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $transaction->transaction_currency_symbol,
                    'currency_code'           => $transaction->transaction_currency_code,
                    'currency_decimal_places' => $transaction->transaction_currency_dp,
                ];
            }
            $return[$costCenter]['spent'][$currencyId]['spent']
                = bcadd($return[$costCenter]['spent'][$currencyId]['spent'], $transaction->transaction_amount);
        }

        return $return;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCostCenter(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCostCenter();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        return (string)$set->sum('transaction_amount');
    }

    /**
     * @param array $data
     *
     * @return CostCenter
     */
    public function store(array $data): CostCenter
    {
        /** @var CostCenterFactory $factory */
        $factory = app(CostCenterFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate(null, $data['name']);
    }

    /**
     * @param CostCenter $costCenter
     * @param array    $data
     *
     * @return CostCenter
     */
    public function update(CostCenter $costCenter, array $data): CostCenter
    {
        /** @var CostCenterUpdateService $service */
        $service = app(CostCenterUpdateService::class);

        return $service->update($costCenter, $data);
    }

    /**
     * @param CostCenter $costCenter
     *
     * @return Carbon|null
     */
    private function getFirstJournalDate(CostCenter $costCenter): ?Carbon
    {
        $query  = $costCenter->transactionJournals()->orderBy('date', 'ASC');
        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param CostCenter $costCenter
     *
     * @return Carbon|null
     */
    private function getFirstTransactionDate(CostCenter $costCenter): ?Carbon
    {
        // check transactions:
        $query = $costCenter->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'ASC');

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }

    /**
     * @param CostCenter   $costCenter
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    private function getLastJournalDate(CostCenter $costCenter, Collection $accounts): ?Carbon
    {
        $query = $costCenter->transactionJournals()->orderBy('date', 'DESC');

        if ($accounts->count() > 0) {
            $query->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('t.account_id', $accounts->pluck('id')->toArray());
        }

        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param CostCenter   $costCenter
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    private function getLastTransactionDate(CostCenter $costCenter, Collection $accounts): ?Carbon
    {
        // check transactions:
        $query = $costCenter->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'DESC');
        if ($accounts->count() > 0) {
            // filter journals:
            $query->whereIn('transactions.account_id', $accounts->pluck('id')->toArray());
        }

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }
}
