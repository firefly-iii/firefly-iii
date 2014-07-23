<?php

use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use Firefly\Storage\Limit\LimitRepositoryInterface as LRI;

class LimitController extends BaseController
{

    protected $_budgets;
    protected $_limits;

    public function __construct(BRI $budgets, LRI $limits)
    {
        $this->_budgets = $budgets;
        $this->_limits = $limits;
        View::share('menu', 'budgets');

    }

    public function create($budgetId = null)
    {
        $periods = [
            'weekly'    => 'A week',
            'monthly'   => 'A month',
            'quarterly' => 'A quarter',
            'half-year' => 'Six months',
            'yearly'    => 'A year',
        ];

        $budget = $this->_budgets->find($budgetId);
        $budget_id = is_null($budget) ? null : $budget->id;
        $budgets = $this->_budgets->getAsSelectList();
        return View::make('limits.create')->with('budgets', $budgets)->with('budget_id', $budget_id)->with(
            'periods', $periods
        );
    }

    public function store()
    {
        // find a limit with these properties, as we might already have one:
        $limit = $this->_limits->store(Input::all());
        if ($limit->id) {
            return Redirect::route('budgets.index');
        } else {
            return Redirect::route('budgets.limits.create')->withInput();
        }
    }

    public function delete($limitId)
    {
        $limit = $this->_limits->find($limitId);


        if ($limit) {
            return View::make('limits.delete')->with('limit', $limit);
        } else {
            return View::make('error')->with('message', 'No such limit!');
        }
    }

    public function destroy($limitId)
    {
        $limit = $this->_limits->find($limitId);


        if ($limit) {
            $limit->delete();
            return Redirect::route('budgets.index');
        } else {
            return View::make('error')->with('message', 'No such limit!');
        }
    }


} 