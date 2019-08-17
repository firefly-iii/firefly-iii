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
use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use FireflyIII\Support\Http\Controllers\UserNavigation;
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
    use UserNavigation, PeriodOverview;

    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * ShowController constructor.
     * @codeCoverageIgnore
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


    /**
     * Show an account.
     *
     * @param Request $request
     * @param Account $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws Exception
     */
    public function show(Request $request, Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end = $end ?? session('end');

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        $objectType       = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));
        $today            = new Carbon;
        $subTitleIcon     = config(sprintf('firefly.subIconsByIdentifier.%s', $account->accountType->type));
        $page             = (int)$request->get('page');
        $pageSize         = (int)app('preferences')->get('listPageSize', 50)->data;
        $currency         = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();
        $fStart           = $start->formatLocalized($this->monthAndDayFormat);
        $fEnd             = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle         = (string)trans('firefly.journals_in_period_for_account', ['name' => $account->name, 'start' => $fStart, 'end' => $fEnd]);
        $chartUri         = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $firstTransaction = $this->repository->oldestJournalDate($account) ?? $start;
        $periods          = $this->getAccountPeriodOverview($account, $firstTransaction, $end);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setAccounts(new Collection([$account]))
            ->setLimit($pageSize)
            ->setPage($page)->withAccountInformation()
            ->setRange($start, $end);
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('accounts.show', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]));
        $showAll = false;

        return view(
            'accounts.show',
            compact(
                'account', 'showAll', 'objectType', 'currency', 'today', 'periods', 'subTitleIcon', 'groups', 'subTitle', 'start', 'end',
                'chartUri'
            )
        );
    }

    /**
     * Show an account.
     *
     * @param Request $request
     * @param Account $account
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     *
     *
     * @throws Exception
     */
    public function showAll(Request $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $isLiability  = $this->repository->isLiability($account);
        $objectType   = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));
        $end          = new Carbon;
        $today        = new Carbon;
        $start        = $this->repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $currency     = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();
        $subTitle     = (string)trans('firefly.all_journals_for_account', ['name' => $account->name]);
        $periods      = new Collection;
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setLimit($pageSize)->setPage($page)->withAccountInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('accounts.show.all', [$account->id]));
        $chartUri = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $showAll  = true;

        return view(
            'accounts.show',
            compact('account', 'showAll', 'objectType', 'isLiability', 'currency', 'today',
                    'chartUri', 'periods', 'subTitleIcon', 'groups', 'subTitle', 'start', 'end')
        );
    }

}
