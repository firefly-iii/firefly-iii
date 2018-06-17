<?php
/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Recurring;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    /** @var RecurringRepositoryInterface */
    private $recurring;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', trans('firefly.recurrences'));
                app('view')->share('subTitle', trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Recurrence $recurrence)
    {
        // get recurrence type:
        // todo move to repository
        /** @var RecurrenceRepetition $repetition */
        $repetition            = $recurrence->recurrenceRepetitions()->first();
        $currentRepetitionType = $repetition->repetition_type;
        if ('' !== $repetition->repetition_moment) {
            $currentRepetitionType .= ',' . $repetition->repetition_moment;
        }

        // todo handle old repetition type as well.

        return view('recurring.edit', compact('recurrence','currentRepetitionType'));
    }


}