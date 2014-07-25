<?php

use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;

/**
 * Class BudgetController
 */
class BudgetController extends BaseController
{

    protected $_budgets;

    /**
     * @param BRI $budgets
     */
    public function __construct(BRI $budgets)
    {
        $this->_budgets = $budgets;
        View::share('menu', 'budgets');
    }

    /**
     * @return $this|\Illuminate\View\View
     * @throws Firefly\Exception\FireflyException
     */
    public function indexByDate()
    {
        // get a list of dates by getting all repetitions:
        $budgets = $this->_budgets->get();
        $reps = [];
        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {
                $dateFormats = \Config::get('firefly.date_formats_by_period.' . $limit->repeat_freq);
                if (is_null($dateFormats)) {
                    throw new \Firefly\Exception\FireflyException('No date formats for ' . $limit->repeat_freq);
                }

                foreach ($limit->limitrepetitions as $rep) {
                    $periodOrder = $rep->startdate->format($dateFormats['group_date']);
                    $period = $rep->startdate->format($dateFormats['display_date']);
                    $reps[$periodOrder] = isset($reps[$periodOrder]) ? $reps[$periodOrder] : ['date' => $period];

                }
            }
        }
        // put all the budgets under their respective date:
        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {
                $dateFormats = \Config::get('firefly.date_formats_by_period.' . $limit->repeat_freq);
                foreach ($limit->limitrepetitions as $rep) {

                    $month = $rep->startdate->format($dateFormats['group_date']);
                    $reps[$month]['limitrepetitions'][] = $rep;
                }
            }
        }
        krsort($reps);

        return View::make('budgets.indexByDate')->with('reps', $reps);

    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function indexByBudget()
    {
        $budgets = $this->_budgets->get();
        $today = new \Carbon\Carbon;
        return View::make('budgets.indexByBudget')->with('budgets', $budgets)->with('today', $today);

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $data = [
            'name'        => Input::get('name'),
            'amount'      => floatval(Input::get('amount')),
            'repeat_freq' => Input::get('period'),
            'repeats'     => intval(Input::get('repeats'))
        ];

        $this->_budgets->store($data);
        Session::flash('success', 'Budget created!');
        return Redirect::route('budgets.index');
    }

    /**
     * TODO actual view, actual content.
     *
     * @param $budgetId
     *
     * @return string
     */
    public function show($budgetId)
    {
        /** @var \Budget $budget */
        $budget = $this->_budgets->find($budgetId);

        $list = $budget->transactionjournals()->get();
        $return = [];
        /** @var \TransactionJournal $entry */
        foreach ($list as $entry) {
            $month = $entry->date->format('F Y');
            $return[$month] = isset($return[$month]) ? $return[$month] : [];
            $return[$month][] = $entry;

        }
        $str = '';

        foreach ($return as $month => $set) {
            $str .= '<h1>' . $month . '</h1>';
            /** @var \TransactionJournal $tj */
            $sum = 0;
            foreach ($set as $tj) {
                $str .= '#' . $tj->id . ' ' . $tj->description . ': ';

                foreach ($tj->transactions as $index => $t) {
                    $str .= $t->amount . ', ';
                    if ($index == 0) {
                        $sum += $t->amount;

                    }
                }
                $str .= '<br>';

            }
            $str .= 'sum: ' . $sum . '<br><br>';
        }

        return $str;


    }


} 