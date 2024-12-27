<?php

/**
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Export;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Export\ExportDataGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private JournalRepositoryInterface $journalRepository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-life-bouy');
                app('view')->share('title', (string) trans('firefly.export_data_title'));
                $this->journalRepository = app(JournalRepositoryInterface::class);
                $this->middleware(IsDemoUser::class)->except(['index']);

                return $next($request);
            }
        );
    }

    /**
     * @throws FireflyException
     */
    public function export(): LaravelResponse|RedirectResponse
    {
        if (auth()->user()->hasRole('demo')) {
            session()->flash('info', (string) trans('firefly.demo_user_export'));

            return redirect(route('export.index'));
        }

        /** @var ExportDataGenerator $generator */
        $generator = app(ExportDataGenerator::class);
        $generator->setUser(auth()->user());

        $generator->setExportTransactions(true);

        // get first transaction in DB:
        $firstDate = today(config('app.timezone'));
        $firstDate->subYear();
        $journal   = $this->journalRepository->firstNull();
        if (null !== $journal) {
            $firstDate = clone $journal->date;
        }
        $generator->setStart($firstDate);
        $result    = $generator->export();

        $name      = sprintf('%s_transaction_export.csv', date('Y_m_d'));
        $quoted    = sprintf('"%s"', addcslashes($name, '"\\'));

        // headers for CSV file.
        /** @var LaravelResponse $response */
        $response  = response($result['transactions']);
        $response
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', 'text/x-csv')
            ->header('Content-Disposition', 'attachment; filename='.$quoted)
            // ->header('Content-Transfer-Encoding', 'binary')
            ->header('Connection', 'Keep-Alive')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'public')
            ->header('Content-Length', (string) strlen($result['transactions']))
        ;

        // return CSV file made from 'transactions' array.
        return $response;
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        return view('export.index');
    }
}
