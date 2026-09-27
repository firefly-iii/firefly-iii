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

use FireflyIII\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class IndexController
 */
final class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(function ($request, $next) {
            app('view')->share('mainTitleIcon', 'bi-credit-card');
            app('view')->share('title', (string) trans('firefly.accounts'));

            return $next($request);
        });
    }

    /**
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function inactive(string $objectType): Factory|\Illuminate\Contracts\View\View
    {
        $subTitle     = (string) trans(sprintf('firefly.%s_accounts_inactive', $objectType));
        $subTitleIcon = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));

        return view('accounts.index', [
            'objectType'   => $objectType,
            'subTitleIcon' => $subTitleIcon,
            'subTitle'     => $subTitle,
        ]);
    }

    /**
     * Show list of accounts.
     *
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(string $objectType): Factory|\Illuminate\Contracts\View\View
    {
        $subTitle     = (string) trans(sprintf('firefly.%s_accounts', $objectType));
        $subTitleIcon = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));

        return view('accounts.index', [
            'objectType'   => $objectType,
            'subTitleIcon' => $subTitleIcon,
            'subTitle'     => $subTitle,
        ]);
    }
}
