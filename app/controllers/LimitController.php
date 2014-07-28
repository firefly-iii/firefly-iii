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
    public function create($budgetId = null)
    {
        $periods = \Config::get('firefly.periods_to_text');
        $prefilled = [
            'startdate'   => Input::get('startdate') ? : date('Y-m-d'),
            'repeat_freq' => Input::get('repeat_freq') ? : 'monthly'
        ];

        $budgets = $this->_budgets->getAsSelectList();

        return View::make('limits.create')->with('budgets', $budgets)->with('budget_id', $budgetId)->with(
            'periods', $periods
        )->with('prefilled', $prefilled);
    }

    /**
     * @param null $limitId
     *
     * @return $this|\Illuminate\View\View
     */
    public function edit($limitId = null)
    {
        $limit = $this->_limits->find($limitId);
        $budgets = $this->_budgets->getAsSelectList();

        $periods = [
            'weekly'    => 'A week',
            'monthly'   => 'A month',
            'quarterly' => 'A quarter',
            'half-year' => 'Six months',
            'yearly'    => 'A year',
        ];


        if ($limit) {
            return View::make('limits.edit')->with('limit', $limit)->with('budgets', $budgets)->with(
                'periods', $periods
            );
        }

        return View::make('error')->with('message', 'No such limit.');

    }

    /**
     * @param null $limitId
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function update($limitId = null)
    {
        /** @var \Limit $limit */
        $limit = $this->_limits->find($limitId);
        if ($limit) {
            $limit->startdate = new \Carbon\Carbon(Input::get('date'));
            $limit->repeat_freq = Input::get('period');
            $limit->repeats = !is_null(Input::get('repeats')) && Input::get('repeats') == '1' ? 1 : 0;
            $limit->amount = floatval(Input::get('amount'));
            if (!$limit->save()) {
                Session::flash('error', 'Could not save new limit: ' . $limit->errors()->first());

                return Redirect::route('budgets.limits.edit', $limit->id)->withInput();
            } else {
                Session::flash('success', 'Limit saved!');
                foreach ($limit->limitrepetitions()->get() as $rep) {
                    $rep->delete();
                }

                return Redirect::route('budgets.index');
            }
        }

        return View::make('error')->with('message', 'No limit!');

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param $limitId
     *
     * @return $this|\Illuminate\View\View
     */
    public function delete($limitId)
    {
        $limit = $this->_limits->find($limitId);


        if ($limit) {
            return View::make('limits.delete')->with('limit', $limit);
        } else {
            return View::make('error')->with('message', 'No such limit!');
        }
    }

    /**
     * @param $limitId
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
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