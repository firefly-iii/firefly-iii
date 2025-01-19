<?php

/*
 * IndexController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\TransactionCurrency;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class IndexController extends Controller
{
    protected CurrencyRepositoryInterface $repository;
    protected UserRepositoryInterface     $userRepository;

    /**
     * CurrencyController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.currencies'));
                app('view')->share('mainTitleIcon', 'fa-usd');
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show overview of currencies.
     *
     * @return Factory|View
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user       = auth()->user();
        $page       = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize   = (int) app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->repository->getAll();

        // order so default and enabled are on top:
        $collection = $collection->sortBy(
            static function (TransactionCurrency $currency) {
                $native = true === $currency->userGroupNative ? 0 : 1;
                $enabled = true === $currency->userGroupEnabled ? 0 : 1;

                return sprintf('%s-%s-%s', $native, $enabled, $currency->code);
            }
        );
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);

        $currencies = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $currencies->setPath(route('currencies.index'));
        $isOwner    = true;
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('info', (string) trans('firefly.ask_site_owner', ['owner' => config('firefly.site_owner')]));
            $isOwner = false;
        }

        return view('currencies.index', compact('currencies', 'isOwner'));
    }
}
