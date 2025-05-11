<?php

/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use BasicDataSupport;

    private AccountRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string) trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return Factory|View
     *
     * @throws FireflyException
     *                                              */
    public function inactive(Request $request, string $objectType)
    {
        $inactivePage  = true;
        $subTitle      = (string) trans(sprintf('firefly.%s_accounts_inactive', $objectType));
        $subTitleIcon  = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $types         = config(sprintf('firefly.accountTypesByIdentifier.%s', $objectType));
        $collection    = $this->repository->getInactiveAccountsByType($types);
        $total         = $collection->count();
        $page          = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize      = (int) app('preferences')->get('listPageSize', 50)->data;
        $accounts      = $collection->slice(($page - 1) * $pageSize, $pageSize);
        unset($collection);

        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());
        $start->subDay();

        $ids           = $accounts->pluck('id')->toArray();
        Log::debug(sprintf('inactive start: finalAccountsBalance("%s")', $start->format('Y-m-d H:i:s')));
        Log::debug(sprintf('inactive end: finalAccountsBalance("%s")', $end->format('Y-m-d H:i:s')));
        $startBalances = Steam::finalAccountsBalance($accounts, $start);
        $endBalances   = Steam::finalAccountsBalance($accounts, $end);
        $activities    = Steam::getLastActivities($ids);


        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances): void {
                $currency                   = $this->repository->getAccountCurrency($account);
                $account->lastActivityDate  = $this->isInArrayDate($activities, $account->id);
                $account->startBalances     = Steam::filterAccountBalance($startBalances[$account->id] ?? [], $account, $this->convertToNative, $currency);
                $account->endBalances       = Steam::filterAccountBalance($endBalances[$account->id] ?? [], $account, $this->convertToNative, $currency);
                $account->differences       = $this->subtract($account->startBalances, $account->endBalances);
                $account->interest          = Steam::bcround($this->repository->getMetaValue($account, 'interest'), 4);
                $account->interestPeriod    = (string) trans(sprintf('firefly.interest_calc_%s', $this->repository->getMetaValue($account, 'interest_period')));
                $account->accountTypeString = (string) trans(sprintf('firefly.account_type_%s', $account->accountType->type));
                $account->current_debt      = '0';
                $account->currency          = $currency ?? $this->defaultCurrency;
                $account->iban              = implode(' ', str_split((string) $account->iban, 4));
            }
        );

        // make paginator:
        $accounts      = new LengthAwarePaginator($accounts, $total, $pageSize, $page);
        $accounts->setPath(route('accounts.inactive.index', [$objectType]));

        return view('accounts.index', compact('objectType', 'inactivePage', 'subTitleIcon', 'subTitle', 'page', 'accounts'));
    }

    private function subtract(array $startBalances, array $endBalances): array
    {
        $result = [];
        foreach ($endBalances as $key => $value) {
            $result[$key] = bcsub((string) $value, $startBalances[$key] ?? '0');
        }

        return $result;
    }

    /**
     * Show list of accounts.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     *                                              */
    public function index(Request $request, string $objectType)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $subTitle      = (string) trans(sprintf('firefly.%s_accounts', $objectType));
        $subTitleIcon  = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $types         = config(sprintf('firefly.accountTypesByIdentifier.%s', $objectType));

        $this->repository->resetAccountOrder();

        $collection    = $this->repository->getActiveAccountsByType($types);
        $total         = $collection->count();
        $page          = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize      = (int) app('preferences')->get('listPageSize', 50)->data;
        $accounts      = $collection->slice(($page - 1) * $pageSize, $pageSize);
        $inactiveCount = $this->repository->getInactiveAccountsByType($types)->count();

        app('log')->debug(sprintf('Count of collection: %d, count of accounts: %d', $total, $accounts->count()));

        unset($collection);

        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());

        // 2025-05-11 removed this so start is exactly the start of the month.
        // $start->subDay();

        $ids           = $accounts->pluck('id')->toArray();
        Log::debug(sprintf('index start: finalAccountsBalance("%s")', $start->format('Y-m-d H:i:s')));
        Log::debug(sprintf('index end: finalAccountsBalance("%s")', $end->format('Y-m-d H:i:s')));
        $startBalances = Steam::finalAccountsBalance($accounts, $start);
        $endBalances   = Steam::finalAccountsBalance($accounts, $end);
        $activities    = Steam::getLastActivities($ids);


        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances): void {
                $interest                     = (string) $this->repository->getMetaValue($account, 'interest');
                $interest                     = '' === $interest ? '0' : $interest;
                $currency                     = $this->repository->getAccountCurrency($account);

                $account->startBalances       = Steam::filterAccountBalance($startBalances[$account->id] ?? [], $account, $this->convertToNative, $currency);
                $account->endBalances         = Steam::filterAccountBalance($endBalances[$account->id] ?? [], $account, $this->convertToNative, $currency);
                $account->differences         = $this->subtract($account->startBalances, $account->endBalances);
                $account->lastActivityDate    = $this->isInArrayDate($activities, $account->id);
                $account->interest            = Steam::bcround($interest, 4);
                $account->interestPeriod      = (string) trans(
                    sprintf('firefly.interest_calc_%s', $this->repository->getMetaValue($account, 'interest_period'))
                );
                $account->accountTypeString   = (string) trans(sprintf('firefly.account_type_%s', $account->accountType->type));
                $account->location            = $this->repository->getLocation($account);
                $account->liability_direction = $this->repository->getMetaValue($account, 'liability_direction');
                $account->current_debt        = $this->repository->getMetaValue($account, 'current_debt') ?? '-';
                $account->currency            = $currency ?? $this->defaultCurrency;
                $account->iban                = implode(' ', str_split((string) $account->iban, 4));


            }
        );
        // make paginator:
        app('log')->debug(sprintf('Count of accounts before LAP: %d', $accounts->count()));

        /** @var LengthAwarePaginator $accounts */
        $accounts      = new LengthAwarePaginator($accounts, $total, $pageSize, $page);
        $accounts->setPath(route('accounts.index', [$objectType]));

        app('log')->debug(sprintf('Count of accounts after LAP (1): %d', $accounts->count()));
        app('log')->debug(sprintf('Count of accounts after LAP (2): %d', $accounts->getCollection()->count()));

        return view('accounts.index', compact('objectType', 'inactiveCount', 'subTitleIcon', 'subTitle', 'page', 'accounts'));
    }
}
