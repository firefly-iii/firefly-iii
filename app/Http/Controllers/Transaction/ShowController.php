<?php
/**
 * ShowController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Transaction;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    /** @var TransactionGroupRepositoryInterface */
    private $groupRepository;

    /**
     * SingleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(TransactionGroup $transactionGroup)
    {
        /** @var TransactionJournal $first */
        $first       = $transactionGroup->transactionJournals->first();
        $groupType   = $first->transactionType->type;
        $description = $transactionGroup->title;
        if ($transactionGroup->transactionJournals()->count() > 1) {
            $description = $first->description;
        }

        $subTitle = sprintf('%s: "%s"', $groupType, $description);

        return view('transactions.show', compact('transactionGroup', 'subTitle'));
    }

}