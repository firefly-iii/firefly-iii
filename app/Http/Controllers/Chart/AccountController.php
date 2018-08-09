<?php
/**
 * AccountController.php
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
/** @noinspection NullPointerExceptionInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountController.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountController extends Controller
{
    use DateCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->generator = app(GeneratorInterface::class);
    }


    /**
     * Shows the balances for all the user's expense accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function expenseAccounts(AccountRepositoryInterface $repository): JsonResponse
    {
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end   = clone session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-accounts');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $start->subDay();

        $accounts      = $repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $startBalances = app('steam')->balancesByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesByAccounts($accounts, $end);
        $chartData     = [];

        foreach ($accounts as $account) {
            $id           = $account->id;
            $startBalance = $startBalances[$id] ?? '0';
            $endBalance   = $endBalances[$id] ?? '0';
            $diff         = bcsub($endBalance, $startBalance);
            if (0 !== bccomp($diff, '0')) {
                $chartData[$account->name] = $diff;
            }
        }

        arsort($chartData);
        $data = $this->generator->singleSet((string)trans('firefly.spent'), $chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Expenses per budget, as shown on account overview.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function expenseBudget(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-budget');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withBudgetInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getJournals();
        $chartData    = [];
        $result       = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlBudgetId      = (int)$transaction->transaction_journal_budget_id;
            $transBudgetId     = (int)$transaction->transaction_budget_id;
            $budgetId          = max($jrnlBudgetId, $transBudgetId);
            $result[$budgetId] = $result[$budgetId] ?? '0';
            $result[$budgetId] = bcadd($transaction->transaction_amount, $result[$budgetId]);
        }

        $names = $this->getBudgetNames(array_keys($result));
        foreach ($result as $budgetId => $amount) {
            $chartData[$names[$budgetId]] = $amount;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses per budget for all time, as shown on account overview.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function expenseBudgetAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->expenseBudget($account, $start, $end);
    }


    /**
     * Expenses per category for one single account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function expenseCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-category');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getJournals();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId           = (int)$transaction->transaction_journal_category_id;
            $transCatId          = (int)$transaction->transaction_category_id;
            $categoryId          = max($jrnlCatId, $transCatId);
            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        $names = $this->getCategoryNames(array_keys($result));
        foreach ($result as $categoryId => $amount) {
            $chartData[$names[$categoryId]] = $amount;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses grouped by category for account.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function expenseCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->expenseCategory($account, $start, $end);
    }


    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function frontpage(AccountRepositoryInterface $repository): JsonResponse
    {
        $start      = clone session('start', Carbon::now()->startOfMonth());
        $end        = clone session('end', Carbon::now()->endOfMonth());
        $defaultSet = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray();
        Log::debug('Default set is ', $defaultSet);
        $frontPage = app('preferences')->get('frontPageAccounts', $defaultSet);


        Log::debug('Frontpage preference set is ', $frontPage->data);
        if (0 === \count($frontPage->data)) {
            $frontPage->data = $defaultSet;
            Log::debug('frontpage set is empty!');
            $frontPage->save();
        }
        $accounts = $repository->getAccountsById($frontPage->data);

        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }


    /**
     * Shows all income per account for each category.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function incomeCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.income-category');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // grab all journals:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::DEPOSIT]);
        $transactions = $collector->getJournals();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId           = (int)$transaction->transaction_journal_category_id;
            $transCatId          = (int)$transaction->transaction_category_id;
            $categoryId          = max($jrnlCatId, $transCatId);
            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        $names = $this->getCategoryNames(array_keys($result));
        foreach ($result as $categoryId => $amount) {
            $chartData[$names[$categoryId]] = $amount;
        }
        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the income grouped by category for an account, in all time.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function incomeCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->incomeCategory($account, $start, $end);
    }


    /**
     * Shows overview of account during a single period.
     *
     * @param Account $account
     * @param Carbon  $start
     *
     * @param Carbon  $end
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function period(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.account.period');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $step      = $this->calculateStep($start, $end);
        $chartData = [];
        $current   = clone $start;
        switch ($step) {
            case '1D':
                $format   = (string)trans('config.month_and_day');
                $range    = app('steam')->balanceInRange($account, $start, $end);
                $previous = array_values($range)[0];
                while ($end >= $current) {
                    $theDate           = $current->format('Y-m-d');
                    $balance           = $range[$theDate] ?? $previous;
                    $label             = $current->formatLocalized($format);
                    $chartData[$label] = (float)$balance;
                    $previous          = $balance;
                    $current->addDay();
                }
                break;
            // @codeCoverageIgnoreStart
            case '1W':
            case '1M':
            case '1Y':
                while ($end >= $current) {
                    $balance           = (float)app('steam')->balance($account, $current);
                    $label             = app('navigation')->periodShow($current, $step);
                    $chartData[$label] = $balance;
                    $current           = app('navigation')->addPeriod($current, $step, 0);
                }
                break;
            // @codeCoverageIgnoreEnd
        }
        $data = $this->generator->singleSet($account->name, $chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the balances for a given set of dates and accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return JsonResponse
     */
    public function report(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }


    /**
     * Shows the balances for all the user's revenue accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function revenueAccounts(AccountRepositoryInterface $repository): JsonResponse
    {
        $start     = clone session('start', Carbon::now()->startOfMonth());
        $end       = clone session('end', Carbon::now()->endOfMonth());
        $chartData = [];
        $cache     = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.revenue-accounts');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $accounts = $repository->getAccountsByType([AccountType::REVENUE]);

        $start->subDay();
        $startBalances = app('steam')->balancesByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesByAccounts($accounts, $end);

        foreach ($accounts as $account) {
            $id           = $account->id;
            $startBalance = $startBalances[$id] ?? '0';
            $endBalance   = $endBalances[$id] ?? '0';
            $diff         = bcsub($endBalance, $startBalance);
            $diff         = bcmul($diff, '-1');
            if (0 !== bccomp($diff, '0')) {
                $chartData[$account->name] = $diff;
            }
        }

        arsort($chartData);
        $data = $this->generator->singleSet((string)trans('firefly.earned'), $chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows an overview of the account balances for a set of accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function accountBalanceChart(Collection $accounts, Carbon $start, Carbon $end): array // chart helper method.
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.account-balance-chart');
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        Log::debug('Regenerate chart.account.account-balance-chart from scratch.');

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);

        $default   = app('amount')->getDefaultCurrency();
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $repository->findNull((int)$accountRepos->getMetaValue($account, 'currency_id'));
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet = [
                'label'           => $account->name,
                'currency_symbol' => $currency->symbol,
                'entries'         => [],
            ];

            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = array_values($range)[0];
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->formatLocalized((string)trans('config.month_and_day'));
                $balance  = isset($range[$format]) ? round($range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return $data;
    }

    /**
     * Get the budget names from a set of budget ID's.
     *
     * @param array $budgetIds
     *
     * @return array
     */
    protected function getBudgetNames(array $budgetIds): array // extract info from array.
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        $grouped    = $budgets->groupBy('id')->toArray();
        $return     = [];
        foreach ($budgetIds as $budgetId) {
            if (isset($grouped[$budgetId])) {
                $return[$budgetId] = $grouped[$budgetId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.no_budget');

        return $return;
    }

    /**
     * Get the category names from a set of category ID's. Small helper function for some of the charts.
     *
     * @param array $categoryIds
     *
     * @return array
     */
    protected function getCategoryNames(array $categoryIds): array // extract info from array.
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $grouped    = $categories->groupBy('id')->toArray();
        $return     = [];
        foreach ($categoryIds as $categoryId) {
            if (isset($grouped[$categoryId])) {
                $return[$categoryId] = $grouped[$categoryId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.noCategory');

        return $return;
    }
}
