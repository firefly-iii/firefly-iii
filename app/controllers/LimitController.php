<?php

use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use Firefly\Storage\Limit\LimitRepositoryInterface as LRI;

/**
 * Class LimitController
 */
class LimitController extends BaseController
{

    protected $_budgets;
    protected $_limits;

    /**
     * @param BRI $budgets
     * @param LRI $limits
     */
    public function __construct(BRI $budgets, LRI $limits)
    {
        $this->_budgets = $budgets;
        $this->_limits = $limits;
        View::share('menu', 'budgets');

    }

    /**
     * @param null $budgetId
     *
     * @return $this|\Illuminate\View\View
     */
    public function create(\Budget $budget = null)
    {
        $periods = \Config::get('firefly.periods_to_text');
        $prefilled = [
            'startdate'   => Input::get('startdate') ? : date('Y-m-d'),
            'repeat_freq' => Input::get('repeat_freq') ? : 'monthly',
            'budget_id'   => $budget ? $budget->id : null
        ];

        $budgets = $this->_budgets->getAsSelectList();

        return View::make('limits.create')->with('budgets', $budgets)->with(
            'periods', $periods
        )->with('prefilled', $prefilled);
    }

    public function delete(\Limit $limit)
    {
        return View::make('limits.delete')->with('limit', $limit);
    }

    public function destroy(\Limit $limit)
    {
        $success = $this->_limits->destroy($limit);

        if ($success) {
            Session::flash('success', 'The envelope was deleted.');
        } else {
            Session::flash('error', 'Could not delete the envelope. Check the logs to be sure.');
        }
        if (Input::get('from') == 'date') {
            return Redirect::route('budgets.index');
        } else {
            return Redirect::route('budgets.index.budget');
        }
    }

    /**
     * @param null $limitId
     *
     * @return $this|\Illuminate\View\View
     */
    public function edit(Limit $limit)
    {
        $budgets = $this->_budgets->getAsSelectList();
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('limits.edit')->with('limit', $limit)->with('budgets', $budgets)->with(
            'periods', $periods
        );
    }

    public function store(Budget $budget = null)
    {

        // find a limit with these properties, as we might already have one:
        $limit = $this->_limits->store(Input::all());
        if ($limit->id) {
            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        } else {
            $budgetId = $budget ? $budget->id : null;

            return Redirect::route('budgets.limits.create', [$budgetId, 'from' => Input::get('from')])->withInput();
        }
    }

    /**
     * @param null $limitId
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function update(\Limit $limit)
    {
        /** @var \Limit $limit */
        $limit->startdate = new \Carbon\Carbon(Input::get('date'));
        $limit->repeat_freq = Input::get('period');
        $limit->repeats = !is_null(Input::get('repeats')) && Input::get('repeats') == '1' ? 1 : 0;
        $limit->amount = floatval(Input::get('amount'));
        if (!$limit->save()) {
            Session::flash('error', 'Could not save new limit: ' . $limit->errors()->first());

            return Redirect::route('budgets.limits.edit', [$limit->id, 'from' => Input::get('from')])->withInput();
        } else {
            Session::flash('success', 'Limit saved!');
            foreach ($limit->limitrepetitions()->get() as $rep) {
                $rep->delete();
            }
            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        }

    }


} 