<?php

use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;

class BudgetController extends BaseController
{

    protected $_budgets;

    public function __construct(BRI $budgets)
    {
        $this->_budgets = $budgets;
        View::share('menu', 'budgets');
    }

    public function index($group = null)
    {

        $opts = ['date', 'budget'];
        $group = in_array($group, $opts) ? $group : 'date';

        switch ($group) {
            case 'date':
                // get a list of dates by getting all repetitions:
                $budgets = $this->_budgets->get();
                $reps = [];
                foreach ($budgets as $budget) {
                    foreach ($budget->limits as $limit) {
                        foreach ($limit->limitrepetitions as $rep) {

                            $monthOrder = $rep->startdate->format('Y-m');
                            $month = $rep->startdate->format('F Y');
                            $reps[$monthOrder] = isset($reps[$monthOrder]) ? $reps[$monthOrder] : ['date' => $month];

                        }
                    }
                }
                // put all the budgets under their respective date:
                foreach ($budgets as $budget) {
                    foreach ($budget->limits as $limit) {
                        foreach ($limit->limitrepetitions as $rep) {
                            $month = $rep->startdate->format('Y-m');
                            $reps[$month]['limitrepetitions'][] = $rep;
                        }
                    }
                }
                krsort($reps);

                return View::make('budgets.index')->with('group', $group)->with('reps',$reps);


                break;
            case 'budget':
                $budgets = $this->_budgets->get();
                $today = new \Carbon\Carbon;
                return View::make('budgets.index')->with('budgets', $budgets)->with('today', $today)->with(
                    'group', $group
                );

                break;
        }


    }

    public function create()
    {

        $periods = [
            'weekly'    => 'A week',
            'monthly'   => 'A month',
            'quarterly' => 'A quarter',
            'half-year' => 'Six months',
            'yearly'    => 'A year',
        ];

        return View::make('budgets.create')->with('periods', $periods);
    }

    public function store()
    {

        $data = [
            'name'        => Input::get('name'),
            'amount'      => floatval(Input::get('amount')),
            'repeat_freq' => Input::get('period'),
            'repeats'     => intval(Input::get('repeats'))
        ];

        $budget = $this->_budgets->create($data);
        Session::flash('success', 'Budget created!');
        return Redirect::route('budgets.index');
    }

    public function show($budgetId)
    {
        $budget = $this->_budgets->find($budgetId);

        $list = $budget->transactionjournals()->get();
        $return = [];
        /** @var \TransactionJournal $entry */
        foreach ($list as $entry) {
            $month = $entry->date->format('F Y');
            $return[$month] = isset($return[$month]) ? $return[$month] : [];

            $return[$month][] = $entry;

        }

        foreach ($return as $month => $set) {
            echo '<h1>' . $month . '</h1>';
            /** @var \TransactionJournal $tj */
            $sum = 0;
            foreach ($set as $tj) {
                echo '#' . $tj->id . ' ' . $tj->description . ': ';

                foreach ($tj->transactions as $index => $t) {
                    echo $t->amount . ', ';
                    if ($index == 0) {
                        $sum += $t->amount;

                    }
                }
                echo '<br>';

            }
            echo 'sum: ' . $sum . '<br><br>';
        }


        exit;

        return View::make('budgets.show');


    }

} 