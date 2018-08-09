<?php
/**
 * ShowController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\UserRedirection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use View;

/**
 * Class ShowController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowController extends Controller
{
    use UserRedirection;

    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository    = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Show an account.
     *
     * @param Request     $request
     * @param Account     $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     *
     * @throws FireflyException
     *
     */
    public function show(Request $request, Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }
        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end = $end ?? session('end');
        if ($end < $start) {
            throw new FireflyException('End is after start!'); // @codeCoverageIgnore
        }

        $what         = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type)); // used for menu
        $today        = new Carbon;
        $subTitleIcon = config(sprintf('firefly.subIconsByIdentifier.%s', $account->accountType->type));
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $currencyId   = (int)$this->repository->getMetaValue($account, 'currency_id');
        $currency     = $this->currencyRepos->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }
        $fStart   = $start->formatLocalized($this->monthAndDayFormat);
        $fEnd     = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle = (string)trans('firefly.journals_in_period_for_account', ['name' => $account->name, 'start' => $fStart, 'end' => $fEnd]);
        $chartUri = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $periods  = $this->getPeriodOverview($account, $end);
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setLimit($pageSize)->setPage($page);
        $collector->setRange($start, $end);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('accounts.show', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]));
        $showAll = false;

        return view(
            'accounts.show',
            compact('account', 'showAll', 'what', 'currency', 'today', 'periods', 'subTitleIcon', 'transactions', 'subTitle', 'start', 'end', 'chartUri')
        );
    }

    /**
     * Show an account.
     *
     * @param Request $request
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     *
     * @throws FireflyException
     *
     */
    public function showAll(Request $request, Account $account)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account); // @codeCoverageIgnore
        }
        $end          = new Carbon;
        $today        = new Carbon;
        $start        = $this->repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $currencyId   = (int)$this->repository->getMetaValue($account, 'currency_id');
        $currency     = $this->currencyRepos->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }
        $subTitle = (string)trans('firefly.all_journals_for_account', ['name' => $account->name]);
        $periods  = new Collection;
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setLimit($pageSize)->setPage($page);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('accounts.show.all', [$account->id]));
        $chartUri = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $showAll  = true;

        return view(
            'accounts.show',
            compact('account', 'showAll', 'currency', 'today', 'chartUri', 'periods', 'subTitleIcon', 'transactions', 'subTitle', 'start', 'end')
        );
    }



    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc etc (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @param Account     $account the account involved
     *
     * @param Carbon|null $date
     *
     * @return Collection
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getPeriodOverview(Account $account, ?Carbon $date): Collection // period overview
    {
        $range = app('preferences')->get('viewRange', '1M')->data;
        $start = $this->repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = $date ?? new Carbon;
        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-show-period-entries');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;
        // loop dates
        foreach ($dates as $currentDate) {

            // try a collector for income:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($currentDate['start'], $currentDate['end'])->setTypes([TransactionType::DEPOSIT])
                      ->withOpposingAccount();
            $earned = (string)$collector->getJournals()->sum('transaction_amount');

            // try a collector for expenses:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($currentDate['start'], $currentDate['end'])->setTypes([TransactionType::WITHDRAWAL])
                      ->withOpposingAccount();
            $spent = (string)$collector->getJournals()->sum('transaction_amount');

            $dateName = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
            /** @noinspection PhpUndefinedMethodInspection */
            $entries->push(
                [
                    'name'   => $dateName,
                    'spent'  => $spent,
                    'earned' => $earned,
                    'start'  => $currentDate['start']->format('Y-m-d'),
                    'end'    => $currentDate['end']->format('Y-m-d'),
                ]
            );
        }

        $cache->store($entries);

        return $entries;
    }
}
