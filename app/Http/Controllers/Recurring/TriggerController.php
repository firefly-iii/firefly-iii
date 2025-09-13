<?php

/*
 * TriggerController.php
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

namespace FireflyIII\Http\Controllers\Recurring;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\TriggerRecurrenceRequest;
use FireflyIII\Jobs\CreateRecurringTransactions;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Class TriggerController
 */
class TriggerController extends Controller
{
    private RecurringRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showCategory', true);

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string) trans('firefly.recurrences'));

                $this->repository = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    public function trigger(Recurrence $recurrence, TriggerRecurrenceRequest $request): RedirectResponse
    {
        $all                        = $request->getAll();
        $date                       = $all['date'];

        // grab the date from the last time the recurrence fired:
        $backupDate                 = $recurrence->latest_date;

        // fire the recurring cron job on the given date, then post-date the created transaction.
        app('log')->info(sprintf('Trigger: will now fire recurring cron job task for date "%s".', $date->format('Y-m-d H:i:s')));

        /** @var CreateRecurringTransactions $job */
        $job                        = app(CreateRecurringTransactions::class);
        $job->setRecurrences(new Collection()->push($recurrence));
        $job->setDate($date);
        $job->setForce(false);
        $job->handle();
        app('log')->debug('Done with recurrence.');

        $groups                     = $job->getGroups();
        $this->repository->markGroupsAsNow($groups);
        $recurrence->latest_date    = $backupDate;
        $recurrence->latest_date_tz = $backupDate?->format('e');
        $recurrence->save();
        Preferences::mark();

        if (0 === $groups->count()) {
            $request->session()->flash('info', (string) trans('firefly.no_new_transaction_in_recurrence'));
        }
        if (1 === $groups->count()) {
            $first = $groups->first();
            $request->session()->flash('success', (string) trans('firefly.stored_journal_no_descr'));
            $request->session()->flash('success_url', route('transactions.show', [$first->id]));
        }

        return redirect(route('recurring.show', [$recurrence->id]));
    }
}
