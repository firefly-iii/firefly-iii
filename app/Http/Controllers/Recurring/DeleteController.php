<?php

/**
 * DeleteController.php
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

namespace FireflyIII\Http\Controllers\Recurring;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var RecurringRepositoryInterface Recurring repository */
    private $recurring;

    /**
     * DeleteController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string) trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete a recurring transaction form.
     *
     * @return Factory|View
     */
    public function delete(Recurrence $recurrence)
    {
        $subTitle        = (string) trans('firefly.delete_recurring', ['title' => $recurrence->title]);
        // put previous url in session
        $this->rememberPreviousUrl('recurrences.delete.url');

        $journalsCreated = $this->recurring->getTransactions($recurrence)->count();

        return view('recurring.delete', compact('recurrence', 'subTitle', 'journalsCreated'));
    }

    /**
     * Destroy the recurring transaction.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(RecurringRepositoryInterface $repository, Request $request, Recurrence $recurrence)
    {
        $repository->destroy($recurrence);
        $request->session()->flash('success', (string) trans('firefly.recurrence_deleted', ['title' => $recurrence->title]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('recurrences.delete.url'));
    }
}
