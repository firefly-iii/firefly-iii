<?php

use Carbon\Carbon;
use Firefly\Helper\Controllers\BudgetInterface as BI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;

/**
 * Class BudgetController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class BudgetController extends BaseController
{

    protected $_budgets;
    protected $_repository;

    /**
     * @param BI  $budgets
     * @param BRI $repository
     */
    public function __construct(BI $budgets, BRI $repository)
    {
        $this->_budgets    = $budgets;
        $this->_repository = $repository;
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('budgets.create')->with('periods', $periods)->with('subTitle', 'Create a new budget');
    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function delete(Budget $budget)
    {
        return View::make('budgets.delete')->with('budget', $budget)
            ->with('subTitle', 'Delete budget "' . $budget->name . '"');
    }

    /**
     * @param Budget $budget
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Budget $budget)
    {
        // remove budget
        Event::fire('budgets.destroy', [$budget]); // just before deletion.
        $this->_repository->destroy($budget);
        Session::flash('success', 'The budget was deleted.');

        // redirect:
        if (Input::get('from') == 'date') {
            return Redirect::route('budgets.index');
        }
        return Redirect::route('budgets.index.budget');

    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function edit(Budget $budget)
    {
        return View::make('budgets.edit')->with('budget', $budget)
            ->with('subTitle', 'Edit budget "' . $budget->name . '"');

    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function indexByBudget()
    {
        $budgets = $this->_repository->get();

        return View::make('budgets.indexByBudget')->with('budgets', $budgets)->with('today', new Carbon)
            ->with('subTitle', 'All your budgets grouped by budget');

    }

    /**
     * @return $this
     */
    public function indexByDate()
    {
        // get a list of dates by getting all repetitions:
        $set     = $this->_repository->get();
        $budgets = $this->_budgets->organizeByDate($set);

        return View::make('budgets.indexByDate')->with('budgets', $budgets)
            ->with('subTitle', 'All your budgets grouped by date');


    }

    /**
     * Three use cases for this view:
     *
     * - Show everything.
     * - Show a specific repetition.
     * - Show everything shows NO repetition.
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return int
     */
    public function show(Budget $budget, \LimitRepetition $repetition = null)
    {
        $useSessionDates = Input::get('useSession') == 'true' ? true : false;
        $view            = null;
        $title           = null;
        \Log::debug('Is envelope true? ' . (Input::get('noenvelope') == 'true'));
        switch (true) {
            case (!is_null($repetition)):
                $data  = $this->_budgets->organizeRepetition($repetition);
                $view  = 1;
                $title = $budget->name . ', ' . $repetition->periodShow() . ', ' . mf(
                        $repetition->limit->amount,
                        false
                    );
                break;
            case (Input::get('noenvelope') == 'true'):
                $data  = $this->_budgets->outsideRepetitions($budget);
                $view  = 2;
                $title = $budget->name . ', transactions outside an envelope';
                break;
            default:
                $data  = $this->_budgets->organizeRepetitions($budget, $useSessionDates);
                $view  = $useSessionDates ? 3 : 4;
                $title = $useSessionDates ? $budget->name . ' in session period' : $budget->name;
                break;
        }

        return View::make('budgets.show')
            ->with('budget', $budget)
            ->with('repetitions', $data)
            ->with('view', $view)
            ->with('highlight', Input::get('highlight'))
            ->with('useSessionDates', $useSessionDates)
            ->with('subTitle', $title);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $budget = $this->_repository->store(Input::all());
        if ($budget->validate()) {
            Event::fire('budgets.store', [$budget]);
            Session::flash('success', 'Budget created!');

            if (Input::get('create') == '1') {
                return Redirect::route('budgets.create', ['from' => Input::get('from')]);
            }

            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        } else {
            Session::flash('error', 'Could not save the new budget');

            return Redirect::route('budgets.create')->withInput()->withErrors($budget->errors());
        }

    }

    /**
     * @param Budget $budget
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Budget $budget)
    {
        $budget = $this->_repository->update($budget, Input::all());
        if ($budget->validate()) {
            Event::fire('budgets.update', [$budget]);
            Session::flash('success', 'Budget "' . $budget->name . '" updated.');

            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        } else {
            Session::flash('error', 'Could not update budget: ' . $budget->errors()->first());

            return Redirect::route('budgets.edit', $budget->id)->withInput()->withErrors($budget->errors());
        }

    }


} 