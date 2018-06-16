<?php
/**
 * CreateController.php
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


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\Request;

/**
 *
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $budgets;
    /** @var PiggyBankRepositoryInterface */
    private $piggyBanks;
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
                app('view')->share('subTitle', trans('firefly.create_new_recurrence'));

                $this->recurring  = app(RecurringRepositoryInterface::class);
                $this->budgets    = app(BudgetRepositoryInterface::class);
                $this->piggyBanks = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        // todo refactor to expandedform method.
        $budgets         = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $piggyBanks      = $this->piggyBanks->getPiggyBanksWithAmount();
        $piggies        = app('expandedform')->makeSelectListWithEmpty($piggyBanks);
        $tomorrow        = new Carbon;
        $tomorrow->addDay();

        // types of repetitions:
        $typesOfRepetitions = [
            'forever'    => trans('firefly.repeat_forever'),
            'until_date' => trans('firefly.repeat_until_date'),
            'times'      => trans('firefly.repeat_times'),
        ];

        // flash some data:
        $preFilled = [
            'first_date'       => $tomorrow->format('Y-m-d'),
            'transaction_type' => 'withdrawal',
            'active'           => $request->old('active') ?? true,
            'apply_rules'      => $request->old('apply_rules') ?? true,
        ];
        $request->session()->flash('preFilled', $preFilled);

        return view('recurring.create', compact('tomorrow', 'preFilled', 'piggies', 'typesOfRepetitions', 'defaultCurrency', 'budgets'));
    }

    /**
     * @param RecurrenceFormRequest $request
     */
    public function store(RecurrenceFormRequest $request)
    {
        $data = $request->getAll();
        $this->recurring->store($data);
        var_dump($data);
        exit;
    }

}