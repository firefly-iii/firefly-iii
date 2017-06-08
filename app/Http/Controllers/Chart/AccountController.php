<?php
/**
 * AccountController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
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
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Response;
use Steam;

/** checked
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class AccountController extends Controller
{

    /** @var  GeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Account $account)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.account.all');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $start      = $repository->oldestJournalDate($account);
        $end        = new Carbon;
        $format     = (string)trans('config.month_and_day');
        $range      = Steam::balanceInRange($account, $start, $end);
        $current    = clone $start;
        $previous   = array_values($range)[0];
        $chartData  = [];

        while ($end >= $current) {
            $theDate           = $current->format('Y-m-d');
            $balance           = $range[$theDate] ?? $previous;
            $label             = $current->formatLocalized($format);
            $chartData[$label] = $balance;
            $previous          = $balance;
            $current->addDay();
        }

        $data = $this->generator->singleSet($account->name, $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for all the user's expense accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts(AccountRepositoryInterface $repository)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = clone session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-accounts');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $start->subDay();

        $accounts      = $repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $startBalances = Steam::balancesByAccounts($accounts, $start);
        $endBalances   = Steam::balancesByAccounts($accounts, $end);
        $chartData     = [];

        foreach ($accounts as $account) {
            $id           = $account->id;
            $startBalance = $startBalances[$id] ?? '0';
            $endBalance   = $endBalances[$id] ?? '0';
            $diff         = bcsub($endBalance, $startBalance);
            if (bccomp($diff, '0') !== 0) {
                $chartData[$account->name] = $diff;
            }
        }

        arsort($chartData);
        $data = $this->generator->singleSet(strval(trans('firefly.spent')), $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseBudget(Account $account, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-budget');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withBudgetInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getJournals();
        $chartData    = [];
        $result       = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlBudgetId      = intval($transaction->transaction_journal_budget_id);
            $transBudgetId     = intval($transaction->transaction_budget_id);
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

        return Response::json($data);
    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseBudgetAll(AccountRepositoryInterface $repository, Account $account)
    {
        $start = $repository->oldestJournalDate($account);
        $end   = Carbon::now();

        return $this->expenseBudget($account, $start, $end);
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseCategory(Account $account, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-category');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getJournals();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId           = intval($transaction->transaction_journal_category_id);
            $transCatId          = intval($transaction->transaction_category_id);
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

        return Response::json($data);

    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseCategoryAll(AccountRepositoryInterface $repository, Account $account)
    {
        $start = $repository->oldestJournalDate($account);
        $end   = Carbon::now();

        return $this->expenseCategory($account, $start, $end);
    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function frontpage(AccountRepositoryInterface $repository)
    {
        $start      = clone session('start', Carbon::now()->startOfMonth());
        $end        = clone session('end', Carbon::now()->endOfMonth());
        $defaultSet = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray();
        Log::debug('Default set is ', $defaultSet);
        $frontPage = Preferences::get('frontPageAccounts', $defaultSet);
        Log::debug('Frontpage preference set is ', $frontPage->data);
        if (count($frontPage->data) === 0) {
            $frontPage->data = $defaultSet;
            Log::debug('frontpage set is empty!');
            $frontPage->save();
        }
        $accounts = $repository->getAccountsById($frontPage->data);

        return Response::json($this->accountBalanceChart($accounts, $start, $end));
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomeCategory(Account $account, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.income-category');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        // grab all journals:
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::DEPOSIT]);
        $transactions = $collector->getJournals();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId           = intval($transaction->transaction_journal_category_id);
            $transCatId          = intval($transaction->transaction_category_id);
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

        return Response::json($data);

    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomeCategoryAll(AccountRepositoryInterface $repository, Account $account)
    {
        $start = $repository->oldestJournalDate($account);
        $end   = Carbon::now();

        return $this->incomeCategory($account, $start, $end);
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws FireflyException
     */
    public function period(Account $account, Carbon $start)
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $end   = Navigation::endOfPeriod($start, $range);
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.period');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $format    = (string)trans('config.month_and_day');
        $range     = Steam::balanceInRange($account, $start, $end);
        $current   = clone $start;
        $previous  = array_values($range)[0];
        $chartData = [];

        while ($end >= $current) {
            $theDate           = $current->format('Y-m-d');
            $balance           = $range[$theDate] ?? $previous;
            $label             = $current->formatLocalized($format);
            $chartData[$label] = $balance;
            $previous          = $balance;
            $current->addDay();
        }

        $data = $this->generator->singleSet($account->name, $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for a given set of dates and accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(Collection $accounts, Carbon $start, Carbon $end)
    {
        return Response::json($this->accountBalanceChart($accounts, $start, $end));
    }

    /**
     * Shows the balances for all the user's revenue accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts(AccountRepositoryInterface $repository)
    {
        $start     = clone session('start', Carbon::now()->startOfMonth());
        $end       = clone session('end', Carbon::now()->endOfMonth());
        $chartData = [];
        $cache     = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.revenue-accounts');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $accounts = $repository->getAccountsByType([AccountType::REVENUE]);

        $start->subDay();
        $startBalances = Steam::balancesByAccounts($accounts, $start);
        $endBalances   = Steam::balancesByAccounts($accounts, $end);

        foreach ($accounts as $account) {
            $id           = $account->id;
            $startBalance = $startBalances[$id] ?? '0';
            $endBalance   = $endBalances[$id] ?? '0';
            $diff         = bcsub($endBalance, $startBalance);
            $diff         = bcmul($diff, '-1');
            if (bccomp($diff, '0') !== 0) {
                $chartData[$account->name] = $diff;
            }
        }

        arsort($chartData);
        $data = $this->generator->singleSet(strval(trans('firefly.spent')), $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows an account's balance for a single month.
     *
     * @param Account $account
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function single(Account $account)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = clone session('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.single');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $format    = (string)trans('config.month_and_day');
        $range     = Steam::balanceInRange($account, $start, $end);
        $current   = clone $start;
        $previous  = array_values($range)[0];
        $chartData = [];

        while ($end >= $current) {
            $theDate           = $current->format('Y-m-d');
            $balance           = $range[$theDate] ?? $previous;
            $label             = $current->formatLocalized($format);
            $chartData[$label] = $balance;
            $previous          = $balance;
            $current->addDay();
        }

        $data = $this->generator->singleSet($account->name, $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    private function accountBalanceChart(Collection $accounts, Carbon $start, Carbon $end): array
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

        $chartData = [];
        foreach ($accounts as $account) {
            $currency     = $repository->find(intval($account->getMeta('currency_id')));
            $currentSet   = [
                'label'           => $account->name,
                'currency_symbol' => $currency->symbol,
                'entries'         => [],
            ];
            $currentStart = clone $start;
            $range        = Steam::balanceInRange($account, $start, clone $end);
            $previous     = array_values($range)[0];
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->formatLocalized(strval(trans('config.month_and_day')));
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
     * @param array $budgetIds
     *
     * @return array
     */
    private function getBudgetNames(array $budgetIds): array
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
        $return[0] = trans('firefly.no_budget');

        return $return;
    }

    /**
     * Small helper function for some of the charts.
     *
     * @param array $categoryIds
     *
     * @return array
     */
    private function getCategoryNames(array $categoryIds): array
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
        $return[0] = trans('firefly.noCategory');

        return $return;
    }

}
