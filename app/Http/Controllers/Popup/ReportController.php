<?php
/**
 * ReportController.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Popup;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Binder\AccountList;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Response;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers\Popup
 */
class ReportController extends Controller
{

    /**
     * @param Request $request
     *
     * @throws FireflyException
     */
    public function info(Request $request)
    {
        $attributes = $request->get('attributes');
        $attributes = $this->parseAttributes($attributes);
        $html       = '';
        switch ($attributes['location']) {
            default:
                throw new FireflyException('Firefly cannot handle "' . e($attributes['location']) . '" ');
            case 'budget-spent-amount':
                $html = $this->budgetSpentAmount($attributes);

                break;
        }

        return Response::json(['html' => $html]);


    }

    /**
     * @param array $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function budgetSpentAmount(array $attributes): string
    {
        // need to find the budget
        // then search for expenses in the given period
        // list them in some table format.
        /** @var BudgetRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $budget     = $repository->find(intval($attributes['budgetId']));
        if (is_null($budget->id)) {
            throw new FireflyException('Could not find a budget for value "' . e($attributes['budgetId']) . '".');
        }

        // get all expenses in budget in period:
        $journals = $repository->getExpenses($budget, $attributes['accounts'], $attributes['startDate'], $attributes['endDate']);

        $view = view('popup.report.budget-spent-amount', compact('journals'))->render();

        return $view;
    }

    /**
     * @param array $attributes
     *
     * @return array
     * @throws FireflyException
     */
    private function parseAttributes(array $attributes): array
    {
        $attributes['location'] = $attributes['location'] ?? '';
        $attributes['accounts'] = AccountList::routeBinder($attributes['accounts'] ?? '', '');
        try {
            $attributes['startDate'] = Carbon::createFromFormat('Ymd', $attributes['startDate']);
        } catch (InvalidArgumentException $e) {
            throw new FireflyException('Could not parse start date "' . e($attributes['startDate']) . '".');
        }

        try {
            $attributes['endDate'] = Carbon::createFromFormat('Ymd', $attributes['endDate']);
        } catch (InvalidArgumentException $e) {
            throw new FireflyException('Could not parse start date "' . e($attributes['endDate']) . '".');
        }


        return $attributes;
    }

}