<?php

use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use Firefly\Storage\Limit\LimitRepositoryInterface as LRI;

/**
 * Class LimitController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
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
        $this->_limits  = $limits;

        View::share('title','Envelopes');
        View::share('mainTitleIcon', 'fa-tasks');
    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function create(\Budget $budget = null)
    {
        $periods   = \Config::get('firefly.periods_to_text');
        $prefilled = [
            'startdate'   => \Input::get('startdate') ? : date('Y-m-d'),
            'repeat_freq' => \Input::get('repeat_freq') ? : 'monthly',
            'budget_id'   => $budget ? $budget->id : null
        ];

        $budgets = $this->_budgets->getAsSelectList();

        return View::make('limits.create')->with('budgets', $budgets)->with(
            'periods', $periods
        )->with('prefilled', $prefilled)->with('subTitle','New envelope');
    }

    /**
     * @param Limit $limit
     *
     * @return $this
     */
    public function delete(\Limit $limit)
    {
        return View::make('limits.delete')->with('limit', $limit)->with('subTitle','Delete envelope');
    }

    /**
     * @param Limit $limit
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(\Limit $limit)
    {
        Event::fire('limits.destroy', [$limit]); // before
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
     * @param Limit $limit
     *
     * @return $this
     */
    public function edit(Limit $limit)
    {
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');

        $budgets    = $toolkit->makeSelectList($this->_budgets->get());
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('limits.edit')->with('limit', $limit)->with('budgets', $budgets)->with(
            'periods', $periods
        )->with('subTitle','Edit envelope');
    }

    /**
     * @param Budget $budget
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(Budget $budget = null)
    {

        // find a limit with these properties, as Firefly might already have one:
        $limit = $this->_limits->store(Input::all());
        if ($limit->validate()) {
            Session::flash('success', 'Envelope created!');
            Event::fire('limits.store', [$limit]);
            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        } else {
            Session::flash('error', 'Could not save new envelope.');
            $budgetId   = $budget ? $budget->id : null;
            $parameters = [$budgetId, 'from' => Input::get('from')];

            return Redirect::route('budgets.limits.create', $parameters)->withInput()
                ->withErrors($limit->errors());
        }
    }

    /**
     * @param Limit $limit
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(\Limit $limit)
    {


        $limit = $this->_limits->update($limit, Input::all());

        if ($limit->validate()) {
            Event::fire('limits.update', [$limit]);
            Session::flash('success', 'Limit saved!');
            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }


        } else {
            Session::flash('error', 'Could not save new limit: ' . $limit->errors()->first());

            return Redirect::route('budgets.limits.edit', [$limit->id, 'from' => Input::get('from')])->withInput()
                ->withErrors($limit->errors());
        }

    }


} 