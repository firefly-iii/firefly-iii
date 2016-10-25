<?php
/**
 * InOutController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Response;

/**
 * Class InOutController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class InOutController extends Controller
{


    public function inOutReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {

        $incomes          = $helper->getIncomeReport($start, $end, $accounts);
        $expenses         = $helper->getExpenseReport($start, $end, $accounts);
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        return Response::json(
            [
                'income'           => view('reports.partials.income', compact('incomes', 'incomeTopLength'))->render(),
                'expenses'         => view('reports.partials.expenses', compact('expenses', 'expenseTopLength'))->render(),
                'incomes_expenses' => view('reports.partials.income-vs-expenses', compact('expenses', 'incomes'))->render(),
            ]
        );
    }

}