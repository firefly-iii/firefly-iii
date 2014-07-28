<?php

use Carbon\Carbon;
use Firefly\Helper\Controllers\BudgetInterface as BI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;

/**
 * Class BudgetController
 */
class BudgetController extends BaseController
{

    protected $_budgets;
    protected $_repository;


    /**
     * @param BI $budgets
     * @param BRI $repository
     */
    public function __construct(BI $budgets, BRI $repository)
    {
        $this->_budgets = $budgets;
        $this->_repository = $repository;
        View::share('menu', 'budgets');
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('budgets.create')->with('periods', $periods);
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function indexByBudget()
    {
        $budgets = $this->_repository->get();
        $today = new Carbon;

        return View::make('budgets.indexByBudget')->with('budgets', $budgets)->with('today', $today);

    }

    /**
     * @return $this|\Illuminate\View\View
     * @throws Firefly\Exception\FireflyException
     */
    public function indexByDate()
    {
        // get a list of dates by getting all repetitions:
        $set = $this->_repository->get();
        $budgets = $this->_budgets->organizeByDate($set);

        return View::make('budgets.indexByDate')->with('budgets', $budgets);


    }

    public function edit(Budget $budget)
    {
        return View::make('budgets.edit')->with('budget', $budget);

    }

    public function update()
    {
        $budget = $this->_repository->update(Input::all());
        Session::flash('success', 'Budget "' . $budget->name . '" updated.');

        if (Input::get('from') == 'date') {
            return Redirect::route('budgets.index');
        } else {
            return Redirect::route('budgets.index.budget');
        }

        return Redirect::route('budgets.index');
    }

    public function delete(Budget $budget)
    {
        return View::make('budgets.delete')->with('budget', $budget);
    }

    public function destroy()
    {
        $result = $this->_repository->destroy(Input::get('id'));
        if ($result === true) {
            Session::flash('success', 'The budget was deleted.');
            if (Input::get('from') == 'date') {
                return Redirect::route('budgets.index');
            } else {
                return Redirect::route('budgets.index.budget');
            }
        } else {
            Session::flash('error', 'Could not delete the budget. Check the logs to be sure.');
        }
        return Redirect::route('budgets.index');

    }

    /**
     * @param Budget $budget
     *
     * @return int
     */
    public function show(Budget $budget)
    {
        return $budget->id;
//        /** @var \Budget $budget */
//        $budget = $this->_budgets->find($budgetId);
//
//        $list = $budget->transactionjournals()->get();
//        $return = [];
//        /** @var \TransactionJournal $entry */
//        foreach ($list as $entry) {
//            $month = $entry->date->format('F Y');
//            $return[$month] = isset($return[$month]) ? $return[$month] : [];
//            $return[$month][] = $entry;
//
//        }
//        $str = '';
//
//        foreach ($return as $month => $set) {
//            $str .= '<h1>' . $month . '</h1>';
//            /** @var \TransactionJournal $tj */
//            $sum = 0;
//            foreach ($set as $tj) {
//                $str .= '#' . $tj->id . ' ' . $tj->description . ': ';
//
//                foreach ($tj->transactions as $index => $t) {
//                    $str .= $t->amount . ', ';
//                    if ($index == 0) {
//                        $sum += $t->amount;
//
//                    }
//                }
//                $str .= '<br>';
//
//            }
//            $str .= 'sum: ' . $sum . '<br><br>';
//        }
//
//        return $str;

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $budget = $this->_repository->store(Input::all());
        if ($budget->id) {
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

            return Redirect::route('budgets.create')->withInput();
        }

    }


} 