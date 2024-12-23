<?php

/**
 * AccountController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountController.
 */
class AccountController extends Controller
{
    use AugumentData;
    use ChartGeneration;
    use DateCalculation;

    protected GeneratorInterface        $generator;
    private AccountRepositoryInterface  $accountRepository;
    private CurrencyRepositoryInterface $currencyRepository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->generator          = app(GeneratorInterface::class);
                $this->accountRepository  = app(AccountRepositoryInterface::class);
                $this->currencyRepository = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows the balances for all the user's expense accounts (on the front page).
     *
     * This chart is (multi) currency aware.
     */
    public function expenseAccounts(): JsonResponse
    {
        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-accounts');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $start->subDay();

        // prep some vars:
        $currencies    = [];
        $chartData     = [];
        $tempData      = [];

        // grab all accounts and names
        $accounts      = $this->accountRepository->getAccountsByType([AccountType::EXPENSE]);
        $accountNames  = $this->extractNames($accounts);

        // grab all balances
        $startBalances = app('steam')->finalAccountsBalance($accounts, $start);
        $endBalances   = app('steam')->finalAccountsBalance($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int) $accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyCode => $endAmount) {
                if (3 !== strlen($currencyCode)) {
                    continue;
                }
                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount = (string) ($startBalances[$accountId][$currencyCode] ?? '0');
                $diff        = bcsub((string) $endAmount, $startAmount);
                $currencies[$currencyCode] ??= $this->currencyRepository->findByCode($currencyCode);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => $diff,
                        'diff_float'  => (float) $diff, // intentional float
                        'currency_id' => $currencies[$currencyCode]->id,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts       = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet
                                    = [
                                        'label'           => (string) trans('firefly.spent'),
                                        'type'            => 'bar',
                                        'currency_symbol' => $currency->symbol,
                                        'currency_code'   => $currency->code,
                                        'entries'         => $this->expandNames($tempData),
                                    ];
            $chartData[$currencyId] = $dataSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = $entry['difference'];
        }

        $data          = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses per budget for all time, as shown on account overview.
     */
    public function expenseBudgetAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? today(config('app.timezone'))->startOfMonth();
        $end   = today(config('app.timezone'));

        return $this->expenseBudget($account, $start, $end);
    }

    /**
     * Expenses per budget, as shown on account overview.
     */
    public function expenseBudget(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache     = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-budget');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withBudgetInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $journals  = $collector->getExtractedJournals();
        $chartData = [];
        $result    = [];
        $budgetIds = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $budgetId              = (int) $journal['budget_id'];
            $key                   = sprintf('%d-%d', $budgetId, $journal['currency_id']);
            $budgetIds[]           = $budgetId;
            if (!array_key_exists($key, $result)) {
                $result[$key] = [
                    'total'           => '0',
                    'budget_id'       => $budgetId,
                    'currency_name'   => $journal['currency_name'],
                    'currency_symbol' => $journal['currency_symbol'],
                    'currency_code'   => $journal['currency_code'],
                ];
            }
            $result[$key]['total'] = bcadd($journal['amount'], $result[$key]['total']);
        }

        $names     = $this->getBudgetNames($budgetIds);

        foreach ($result as $row) {
            $budgetId          = $row['budget_id'];
            $name              = $names[$budgetId];
            $label             = (string) trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency_name']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol'], 'currency_code' => $row['currency_code']];
        }

        $data      = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses grouped by category for account.
     */
    public function expenseCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? today(config('app.timezone'))->startOfMonth();
        $end   = today(config('app.timezone'));

        return $this->expenseCategory($account, $start, $end);
    }

    /**
     * Expenses per category for one single account.
     */
    public function expenseCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache     = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-category');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $journals  = $collector->getExtractedJournals();
        $result    = [];
        $chartData = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $key                   = sprintf('%d-%d', $journal['category_id'], $journal['currency_id']);
            if (!array_key_exists($key, $result)) {
                $result[$key] = [
                    'total'           => '0',
                    'category_id'     => (int) $journal['category_id'],
                    'currency_name'   => $journal['currency_name'],
                    'currency_symbol' => $journal['currency_symbol'],
                    'currency_code'   => $journal['currency_code'],
                ];
            }
            $result[$key]['total'] = bcadd($journal['amount'], $result[$key]['total']);
        }
        $names     = $this->getCategoryNames(array_keys($result));

        foreach ($result as $row) {
            $categoryId        = $row['category_id'];
            $name              = $names[$categoryId] ?? '(unknown)';
            $label             = (string) trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency_name']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol'], 'currency_code' => $row['currency_code']];
        }

        $data      = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @throws FireflyException
     *                                              */
    public function frontpage(AccountRepositoryInterface $repository): JsonResponse
    {
        $start          = clone session('start', today(config('app.timezone'))->startOfMonth());
        $end            = clone session('end', today(config('app.timezone'))->endOfMonth());
        $defaultSet     = $repository->getAccountsByType([AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value])->pluck('id')->toArray();
        Log::debug('Default set is ', $defaultSet);
        $frontpage      = app('preferences')->get('frontpageAccounts', $defaultSet);
        $frontpageArray = !is_array($frontpage->data) ? [] : $frontpage->data;
        Log::debug('Frontpage preference set is ', $frontpageArray);
        if (0 === count($frontpageArray)) {
            app('preferences')->set('frontpageAccounts', $defaultSet);
            Log::debug('frontpage set is empty!');
        }
        $accounts       = $repository->getAccountsById($frontpageArray);

        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }

    /**
     * Shows the income grouped by category for an account, in all time.
     */
    public function incomeCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? today(config('app.timezone'))->startOfMonth();
        $end   = today(config('app.timezone'));

        return $this->incomeCategory($account, $start, $end);
    }

    /**
     * Shows all income per account for each category.
     */
    public function incomeCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache     = new CacheProperties();
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.income-category');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        // grab all journals:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::DEPOSIT]);
        $journals  = $collector->getExtractedJournals();
        $result    = [];
        $chartData = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $key                   = sprintf('%d-%d', $journal['category_id'], $journal['currency_id']);
            if (!array_key_exists($key, $result)) {
                $result[$key] = [
                    'total'           => '0',
                    'category_id'     => $journal['category_id'],
                    'currency_name'   => $journal['currency_name'],
                    'currency_symbol' => $journal['currency_symbol'],
                    'currency_code'   => $journal['currency_code'],
                ];
            }
            $result[$key]['total'] = bcadd($journal['amount'], $result[$key]['total']);
        }

        $names     = $this->getCategoryNames(array_keys($result));
        foreach ($result as $row) {
            $categoryId        = $row['category_id'];
            $name              = $names[$categoryId] ?? '(unknown)';
            $label             = (string) trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency_name']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol'], 'currency_code' => $row['currency_code']];
        }
        $data      = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows overview of account during a single period.
     *
     * @throws FireflyException
     */
    public function period(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $chartData  = [];
        $cache      = new CacheProperties();
        $cache->addProperty('chart.account.period');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $currencies = $this->accountRepository->getUsedCurrencies($account);

        // if the account is not expense or revenue, just use the account's default currency.
        if (!in_array($account->accountType->type, [AccountType::REVENUE, AccountType::EXPENSE], true)) {
            $currencies = [$this->accountRepository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency()];
        }

        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $chartData[] = $this->periodByCurrency($start, $end, $account, $currency);
        }

        $data       = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * @throws FireflyException
     */
    private function periodByCurrency(Carbon $start, Carbon $end, Account $account, TransactionCurrency $currency): array
    {
        Log::debug(sprintf('Now in periodByCurrency("%s", "%s", %s, "%s")', $start->format('Y-m-d'), $end->format('Y-m-d'), $account->id, $currency->code));
        $locale            = app('steam')->getLocale();
        $step              = $this->calculateStep($start, $end);
        $result            = [
            'label'           => sprintf('%s (%s)', $account->name, $currency->symbol),
            'currency_symbol' => $currency->symbol,
            'currency_code'   => $currency->code,
        ];
        $entries           = [];
        $current           = clone $start;
        Log::debug(sprintf('Step is %s', $step));

        // fix for issue https://github.com/firefly-iii/firefly-iii/issues/8041
        // have to make sure this chart is always based on the balance at the END of the period.
        // This period depends on the size of the chart
        $current           = app('navigation')->endOfX($current, $step, null);
        Log::debug(sprintf('$current date is %s', $current->format('Y-m-d')));
        if ('1D' === $step) {
            // per day the entire period, balance for every day.
            $format   = (string) trans('config.month_and_day_js', [], $locale);
            $range    = app('steam')->finalAccountBalanceInRange($account, $start, $end);
            $previous = array_values($range)[0];
            while ($end >= $current) {
                $theDate         = $current->format('Y-m-d');
                $balance         = $range[$theDate]['balance'] ?? $previous;
                $label           = $current->isoFormat($format);
                $entries[$label] = (float) $balance;
                $previous        = $balance;
                $current->addDay();
            }
        }
        if ('1W' === $step || '1M' === $step || '1Y' === $step) {
            while ($end >= $current) {
                Log::debug(sprintf('Current is: %s', $current->format('Y-m-d')));
                $balance         = Steam::finalAccountBalance($account, $current)[$currency->code] ?? '0';
                $label           = app('navigation')->periodShow($current, $step);
                $entries[$label] = $balance;
                $current         = app('navigation')->addPeriod($current, $step, 0);
                // here too, to fix #8041, the data is corrected to the end of the period.
                $current         = app('navigation')->endOfX($current, $step, null);
            }
        }
        $result['entries'] = $entries;

        return $result;
    }

    /**
     * Shows the balances for a given set of dates and accounts.
     *
     * TODO this chart is not multi currency aware.
     *
     * @throws FireflyException
     */
    public function report(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }

    /**
     * Shows the balances for all the user's revenue accounts.
     *
     * This chart is multi-currency aware.
     */
    public function revenueAccounts(): JsonResponse
    {
        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.revenue-accounts');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $start->subDay();

        // prep some vars:
        $currencies    = [];
        $chartData     = [];
        $tempData      = [];

        // grab all accounts and names
        $accounts      = $this->accountRepository->getAccountsByType([AccountType::REVENUE]);
        $accountNames  = $this->extractNames($accounts);

        // grab all balances
        $startBalances = app('steam')->finalAccountsBalance($accounts, $start);
        $endBalances   = app('steam')->finalAccountsBalance($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int) $accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyCode => $endAmount) {
                if (3 !== strlen($currencyCode)) {
                    continue;
                }

                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount = (string) ($startBalances[$accountId][$currencyCode] ?? '0');
                $diff        = bcsub((string) $endAmount, $startAmount);
                $currencies[$currencyCode] ??= $this->currencyRepository->findByCode($currencyCode);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => $diff,
                        'diff_float'  => (float) $diff, // intentional float
                        'currency_id' => $currencies[$currencyCode]->id,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts       = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_ASC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet
                                    = [
                                        'label'           => (string) trans('firefly.earned'),
                                        'type'            => 'bar',
                                        'currency_symbol' => $currency->symbol,
                                        'currency_code'   => $currency->code,
                                        'entries'         => $this->expandNames($tempData),
                                    ];
            $chartData[$currencyId] = $dataSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = bcmul($entry['difference'], '-1');
        }

        $data          = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }
}
