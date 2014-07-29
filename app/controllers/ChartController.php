<?php

use Firefly\Helper\Controllers\ChartInterface;

/**
 * Class ChartController
 */
class ChartController extends BaseController
{

    protected $_chart;


    /**
     * @param ChartInterface $chart
     */
    public function __construct(ChartInterface $chart)
    {
        $this->_chart = $chart;
    }

    /**
     * @param Account $account
     * @return mixed
     */
    public function homeAccount(Account $account = null)
    {


        if (!is_null($account)) {
            $data = $this->_chart->account($account);
        } else {
            $data = $this->_chart->accounts();
        }
        return Response::json($data);
    }

    /**
     * Return some beneficiary info for an account and a date.
     *
     * @param $name
     * @param $day
     * @param $month
     * @param $year
     *
     * @return $this|\Illuminate\View\View
     */
    public function homeAccountInfo($name, $day, $month, $year)
    {
        $account = $this->_accounts->findByName($name);
        $result = [];
        $sum = 0;
        if ($account) {
            $date = \Carbon\Carbon::createFromDate($year, $month, $day);
            $journals = $this->_journals->getByAccountAndDate($account, $date);
            // loop all journals:
            foreach ($journals as $journal) {
                foreach ($journal->transactions as $transaction) {
                    $name = $transaction->account->name;
                    if ($transaction->account->id != $account->id) {
                        $result[$name] = isset($result[$name]) ? $result[$name] + floatval($transaction->amount)
                            : floatval($transaction->amount);
                        $sum += floatval($transaction->amount);
                    }
                }
            }
        }

        return View::make('charts.info')->with('rows', $result)->with('sum', $sum);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws Firefly\Exception\FireflyException
     */
    public function homeCategories()
    {
        list($start, $end) = $this->_tk->getDateRangeDates();
        $result = [];
        // grab all transaction journals in this period:
        $journals = $this->_journals->getByDateRange($start, $end);

        foreach ($journals as $journal) {
            // has to be one:

            if (!isset($journal->transactions[0])) {
                throw new FireflyException('Journal #' . $journal->id . ' has ' . count($journal->transactions)
                    . ' transactions!');
            }
            $transaction = $journal->transactions[0];
            $amount = floatval($transaction->amount);

            // get budget from journal:
            $budget = $journal->categories()->first();
            $budgetName = is_null($budget) ? '(no category)' : $budget->name;

            $result[$budgetName] = isset($result[$budgetName]) ? $result[$budgetName] + floatval($amount) : $amount;

        }
        unset($journal, $transaction, $budget, $amount);

        // sort
        arsort($result);
        $chartData = [
        ];
        foreach ($result as $name => $value) {
            $chartData[] = [$name, $value];
        }


        return Response::json($chartData);

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws Firefly\Exception\FireflyException
     */
    public function homeBudgets()
    {
        // grab all budgets in the time period, like the index does:
        // get the budgets for this period:
        $data = [];

        list($start) = $this->_tk->getDateRangeDates();
        $budgets = $this->_budgets->getWithRepetitionsInPeriod($start, \Session::get('range'));

        $repeatFreq = Config::get('firefly.range_to_repeat_freq.' . Session::get('range'));


        $limitInPeriod = 'Envelope for XXX';
        $spentInPeriod = 'Spent in XXX';

        $data['series'] = [
            [
                'name' => $limitInPeriod,
                'data' => []
            ],
            [
                'name' => $spentInPeriod,
                'data' => []
            ],
        ];


        foreach ($budgets as $budget) {
            if ($budget->count > 0) {
                $data['labels'][] = wordwrap($budget->name, 12, "<br>");
            }
            foreach ($budget->limits as $limit) {
                foreach ($limit->limitrepetitions as $rep) {
                    //0: envelope for period:
                    $amount = floatval($rep->amount);
                    $spent = $rep->spent;
                    $color = $spent > $amount ? '#FF0000' : null;
                    $data['series'][0]['data'][] = $amount;
                    $data['series'][1]['data'][] = ['y' => $rep->spent, 'color' => $color];
                }
            }


        }

        return Response::json($data);
    }
}