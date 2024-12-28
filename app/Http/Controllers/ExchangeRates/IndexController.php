<?php

/*
 * IndexController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\ExchangeRates;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IndexController extends Controller
{
    /**
     * AttachmentController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-exchange');
                app('view')->share('title', (string) trans('firefly.header_exchange_rates'));

                return $next($request);
            }
        );
        if(!config('cer.enabled'))  {
            throw new NotFoundHttpException();
        }
    }

    public function index(): View
    {
        return view('exchange-rates.index');
    }

    public function rates(TransactionCurrency $from, TransactionCurrency $to): View
    {
        return view('exchange-rates.rates', compact('from', 'to'));
    }
}
