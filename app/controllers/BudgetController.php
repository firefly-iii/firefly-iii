<?php

use Carbon\Carbon;
use FireflyIII\Database\Budget\Budget as BudgetRepository;
use FireflyIII\Shared\Preferences\PreferencesInterface as Pref;

/**
 * Class BudgetController
 *
 * TODO move AJAX methods to their own BudgetAjaxController
 * TODO Find out what constitutes proper camelCase
 * TODO How is object coupling counted?
 *
 */
class BudgetController extends BaseController
{

    /** @var Pref */
    protected $_preferences;
    /** @var BudgetRepository */
    protected $_repository;

    /**
     * @param BudgetRepository $repository
     * @param Pref             $preferences
     */
    public function __construct(BudgetRepository $repository, Pref $preferences)
    {
        $this->_repository  = $repository;
        $this->_preferences = $preferences;
        View::share('title', 'Budgets');
        View::share('mainTitleIcon', 'fa-tasks');
    }

    /**
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function amount(Budget $budget)
    {
        $amount          = intval(Input::get('amount'));
        $date            = Session::get('start', Carbon::now()->startOfMonth());
        $limitRepetition = $this->_repository->updateLimitAmount($budget, $date, $amount);

        return Response::json(['name' => $budget->name, 'repetition' => $limitRepetition->id]);

    }

    /**
     * @return $this
     */
    public function create()
    {
        return View::make('budgets.create')->with('subTitle', 'Create a new budget');
    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function delete(Budget $budget)
    {
        $subTitle = 'Delete budget "' . e($budget->name) . '"';

        return View::make('budgets.delete', compact('budget', 'subTitle'));
    }

    /**
     * @param Budget $budget
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Budget $budget)
    {
        Session::flash('success', 'Budget "' . e($budget->name) . '" was deleted.');
        $this->_repository->destroy($budget);


        return Redirect::route('budgets.index');

    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function edit(Budget $budget)
    {
        $subTitle = 'Edit budget "' . e($budget->name) . '"';

        return View::make('budgets.edit', compact('budget', 'subTitle'));

    }

    /**
     * The index of the budget controller contains all budgets and the current relevant limit repetition.
     *
     * @return $this
     */
    public function index()
    {
        $budgets = $this->_repository->get();

        // loop the budgets:
        $budgets->each(
            function (Budget $budget) {
                /** @noinspection PhpUndefinedFieldInspection */
                $budget->spent = $this->_repository->spentInMonth($budget, \Session::get('start', Carbon::now()->startOfMonth()));
                /** @noinspection PhpUndefinedFieldInspection */
                $budget->currentRep = $budget->limitrepetitions()->where(
                    'limit_repetitions.startdate', \Session::get('start', Carbon::now()->startOfMonth())->format('Y-m-d')
                )->first(
                    ['limit_repetitions.*']
                );
            }
        );

        $spent     = $budgets->sum('spent');
        $amount    = $this->_preferences->get('budgetIncomeTotal' . \Session::get('start', Carbon::now()->startOfMonth())->format('FY'), 1000)->data;
        $overspent = $spent > $amount;
        $spentPCT  = $overspent ? ceil($amount / $spent * 100) : ceil($spent / $amount * 100);

        return View::make('budgets.index', compact('budgets', 'spent', 'spentPCT', 'overspent', 'amount'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateIncome()
    {
        $this->_preferences->set('budgetIncomeTotal' . Session::get('start', Carbon::now()->startOfMonth())->format('FY'), intval(Input::get('amount')));

        return Redirect::route('budgets.index');
    }

    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return \Illuminate\View\View
     */
    public function show(Budget $budget, LimitRepetition $repetition = null)
    {
        if (!is_null($repetition) && $repetition->budgetLimit->budget->id != $budget->id) {
            App::abort(500);
        }

        $hideBudget = true; // used in transaction list.
        $journals   = $this->_repository->getJournals($budget, $repetition);
        $limits     = $repetition ? [$repetition->budgetLimit] : $budget->budgetLimits()->orderBy('startdate', 'DESC')->get();
        $subTitle   = $repetition ? e($budget->name) . ' in ' . $repetition->startdate->format('F Y') : e($budget->name);

        return View::make('budgets.show', compact('limits', 'budget', 'repetition', 'journals', 'subTitle', 'hideBudget'));
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $data = Input::except('_token');

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not validate budget: ' . $messages['errors']->first());
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('budgets.create')->withInput();
        }

        // store:
        $this->_repository->store($data);
        Session::flash('success', 'Budget "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('budgets.index');
        }

        // create another.
        if ($data['post_submit_action'] == 'create_another') {
            return Redirect::route('budgets.create')->withInput();
        }

        return Redirect::route('budgets.index');
    }


    /**
     * @param Budget $budget
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Budget $budget)
    {

        $data = Input::except('_token');

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update budget: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('budgets.edit', $budget->id)->withInput();
        }

        // update
        $this->_repository->update($budget, $data);
        Session::flash('success', 'Budget "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('budgets.index');
        }
        // go back to update screen.
        if ($data['post_submit_action'] == 'return_to_edit') {
            return Redirect::route('budgets.edit', $budget->id)->withInput(['post_submit_action' => 'return_to_edit']);
        }

        return Redirect::route('budgets.index');
    }

    /**
     * @return $this
     */
    public function updateIncome()
    {
        $budgetAmount = $this->_preferences->get('budgetIncomeTotal' . Session::get('start', Carbon::now()->startOfMonth())->format('FY'), 1000);

        return View::make('budgets.income')->with('amount', $budgetAmount);
    }
}
