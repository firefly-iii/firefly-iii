<?php

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Firefly\Helper\Controllers\BudgetInterface as BI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\MessageBag;


class BudgetController extends BaseController
{


    public function __construct()
    {
        View::share('title', 'Budgets');
        View::share('mainTitleIcon', 'fa-tasks');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateIncome()
    {
        /** @var \Firefly\Helper\Preferences\PreferencesHelperInterface $preferences */
        $preferences = App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $date        = Session::get('start');

        $value = intval(Input::get('amount'));
        $preferences->set('budgetIncomeTotal' . $date->format('FY'), $value);
        return Redirect::route('budgets.index');
    }

    /**
     * Update the amount for a budget's limitrepetition and/or create it.
     *
     * @param Budget $budget
     */
    public function amount(Budget $budget)
    {
        $amount = intval(Input::get('amount'));
        $date   = Session::get('start');
        /** @var \Limit $limit */
        $limit = $budget->limits()->where('startdate', $date->format('Y-m-d'))->first();
        if (!$limit) {
            // create one!
            $limit = new Limit;
            $limit->budget()->associate($budget);
            $limit->startdate   = $date;
            $limit->amount      = $amount;
            $limit->repeat_freq = 'monthly';
            $limit->repeats     = 0;
            $limit->save();
            Event::fire('limits.store', [$limit]);

        } else {
            if ($amount > 0) {
                $limit->amount = $amount;
                $limit->save();
                Event::fire('limits.update', [$limit]);
            } else {
                $limit->delete();
            }
        }
        // try to find the limit repetition for this limit:
        $repetition = $limit->limitrepetitions()->first();
        if ($repetition) {
            return Response::json(['name' => $budget->name, 'repetition' => $repetition->id]);
        } else {
            return Response::json(['name' => $budget->name, 'repetition' => null]);
        }

    }

    public function index()
    {

        /** @var \Firefly\Helper\Preferences\PreferencesHelperInterface $preferences */
        $preferences = App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $date        = Session::get('start');

        /** @var \FireflyIII\Database\Budget $repos */
        $repos   = App::make('FireflyIII\Database\Budget');
        $budgets = $repos->get();

        // get the limits for the current month.
        $date = \Session::get('start');
        /** @var \Budget $budget */
        foreach ($budgets as $budget) {

            $budget->spent = $repos->spentInMonth($budget, $date);
            $budget->pct   = 0;
            $budget->limit = 0;

            /** @var \Limit $limit */
            foreach ($budget->limits as $limit) {
                /** @var \LimitRepetition $repetition */
                foreach ($limit->limitrepetitions as $repetition) {
                    if ($repetition->startdate == $date) {
                        $budget->currentRep = $repetition;
                        $budget->limit      = floatval($repetition->amount);
                        if ($budget->limit > $budget->spent) {
                            // not overspent:
                            $budget->pct = 30;
                        } else {
                            $budget->pct = 50;
                        }

                    }
                }
            }
        }

        $budgetAmount = $preferences->get('budgetIncomeTotal' . $date->format('FY'), 1000);

        return View::make('budgets.index', compact('budgets'))->with('budgetAmount', $budgetAmount);
    }

    /**
     * @return $this
     */
    public function updateIncome()
    {
        $date = Session::get('start');
        /** @var \Firefly\Helper\Preferences\PreferencesHelperInterface $preferences */
        $preferences  = App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $budgetAmount = $preferences->get('budgetIncomeTotal' . $date->format('FY'), 1000);
        return View::make('budgets.income')->with('amount', $budgetAmount)->with('date', $date);
    }

    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return \Illuminate\View\View
     */
    public function show(Budget $budget, LimitRepetition $repetition = null)
    {
        if (!is_null($repetition) && $repetition->limit->budget->id != $budget->id) {
            App::abort(500);
        }

        return View::make('budgets.show');
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
        return View::make('budgets.delete')->with('budget', $budget)->with('subTitle', 'Delete budget "' . $budget->name . '"');
    }

    public function destroy(Budget $budget)
    {
        /** @var \FireflyIII\Database\Budget $repos */
        $repos = App::make('FireflyIII\Database\Budget');
        // remove budget
        $repos->destroy($budget);
        Session::flash('success', 'The budget was deleted.');
        return Redirect::route('budgets.index');

    }

    /**
     * @param Budget $budget
     *
     * @return $this
     */
    public function edit(Budget $budget)
    {
        Session::flash('prefilled', ['name' => $budget->name]);
        return View::make('budgets.edit')->with('budget', $budget)->with('subTitle', 'Edit budget "' . $budget->name . '"');

    }

//    /**
//     * @return $this|\Illuminate\View\View
//     */
//    public function indexByBudget()
//    {
//        View::share('subTitleIcon', 'fa-folder-open');
//
//        $budgets = $this->_repository->get();
//
//        return View::make('budgets.indexByBudget')->with('budgets', $budgets)->with('today', new Carbon)
//                   ->with('subTitle', 'Grouped by budget');
//
//    }
//
//    /**
//     * @return $this
//     */
//    public function indexByDate()
//    {
//        View::share('subTitleIcon', 'fa-calendar');
//
//        // get a list of dates by getting all repetitions:
//        $set     = $this->_repository->get();
//        $budgets = $this->_budgets->organizeByDate($set);
//
//        return View::make('budgets.indexByDate')->with('budgets', $budgets)
//                   ->with('subTitle', 'Grouped by date');
//
//
//    }
//
//    /**
//     * Three use cases for this view:
//     *
//     * - Show everything.
//     * - Show a specific repetition.
//     * - Show everything shows NO repetition.
//     *
//     * @param Budget          $budget
//     * @param LimitRepetition $repetition
//     *
//     * @return int
//     */
//    public function show(Budget $budget, \LimitRepetition $repetition = null)
//    {
//        $useSessionDates = Input::get('useSession') == 'true' ? true : false;
//        $view            = null;
//        $title           = null;
//        \Log::debug('Is envelope true? ' . (Input::get('noenvelope') == 'true'));
//        switch (true) {
//            case (!is_null($repetition)):
//                $data  = $this->_budgets->organizeRepetition($repetition);
//                $view  = 1;
//                $title = $budget->name . ', ' . $repetition->periodShow() . ', ' . mf(
//                        $repetition->limit->amount,
//                        false
//                    );
//                break;
//            case (Input::get('noenvelope') == 'true'):
//                $data  = $this->_budgets->outsideRepetitions($budget);
//                $view  = 2;
//                $title = $budget->name . ', transactions outside an envelope';
//                break;
//            default:
//                $data  = $this->_budgets->organizeRepetitions($budget, $useSessionDates);
//                $view  = $useSessionDates ? 3 : 4;
//                $title = $useSessionDates ? $budget->name . ' in session period' : $budget->name;
//                break;
//        }
//
//        return View::make('budgets.show')
//                   ->with('budget', $budget)
//                   ->with('repetitions', $data)
//                   ->with('view', $view)
//                   ->with('highlight', Input::get('highlight'))
//                   ->with('useSessionDates', $useSessionDates)
//                   ->with('subTitle', 'Overview for ' . $title);
//    }
//
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        /** @var \FireflyIII\Database\Budget $repos */
        $repos = App::make('FireflyIII\Database\Budget');
        $data  = Input::except('_token');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'create_another':
            case 'store':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save budget: ' . $messages['errors']->first());
                    return Redirect::route('budgets.create')->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->store($data);
                Session::flash('success', 'New budget stored!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('budgets.create');
                } else {
                    return Redirect::route('budgets.index');
                }
                break;
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('budgets.create')->withInput();
                break;
        }
    }
//
    /**
     * @param Budget $budget
     *
     * @throws FireflyException
     */
    public function update(Budget $budget)
    {

        /** @var \FireflyIII\Database\Budget $repos */
        $repos = App::make('FireflyIII\Database\Budget');
        $data  = Input::except('_token');

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e(Input::get('post_submit_action')) . '"');
                break;
            case 'create_another':
            case 'update':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save budget: ' . $messages['errors']->first());
                    return Redirect::route('budgets.edit', $budget->id)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->update($budget, $data);
                Session::flash('success', 'Budget updated!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('budgets.edit', $budget->id);
                } else {
                    return Redirect::route('budgets.index');
                }
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('budgets.edit', $budget->id)->withInput();
                break;
        }

//        $budget = $this->_repository->update($budget, Input::all());
//        if ($budget->validate()) {
//            Event::fire('budgets.update', [$budget]);
//            Session::flash('success', 'Budget "' . $budget->name . '" updated.');
//
//            if (Input::get('from') == 'date') {
//                return Redirect::route('budgets.index');
//            } else {
//                return Redirect::route('budgets.index.budget');
//            }
//        } else {
//            Session::flash('error', 'Could not update budget: ' . $budget->errors()->first());
//
//            return Redirect::route('budgets.edit', $budget->id)->withInput()->withErrors($budget->errors());
//        }
//
    }

//    public function nobudget($view = 'session') {
//        switch($view) {
//            default:
//                throw new FireflyException('Cannot show transactions without a budget for view "'.$view.'".');
//                break;
//            case 'session':
//                $start = Session::get('start');
//                $end   = Session::get('end');
//                break;
//        }
//
//        // Add expenses that have no budget:
//        $set = \Auth::user()->transactionjournals()->whereNotIn(
//            'transaction_journals.id', function ($query) use ($start, $end) {
//                $query->select('transaction_journals.id')->from('transaction_journals')
//                      ->leftJoin(
//                          'component_transaction_journal', 'component_transaction_journal.transaction_journal_id', '=',
//                          'transaction_journals.id'
//                      )
//                      ->leftJoin('components', 'components.id', '=', 'component_transaction_journal.component_id')
//                      ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
//                      ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
//                      ->where('components.class', 'Budget');
//            }
//        )->before($end)->after($start)->get();
//
//        return View::make('budgets.nobudget')
//                   ->with('view', $view)
//                   ->with('transactions',$set)
//                   ->with('subTitle', 'Transactions without a budget');
//    }


} 