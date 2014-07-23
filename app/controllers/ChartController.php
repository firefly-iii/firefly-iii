<?php

use Firefly\Exception\FireflyException;
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Helper\Toolkit\ToolkitInterface as tk;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class ChartController
 */
class ChartController extends BaseController
{

    protected $_accounts;
    protected $_journals;
    protected $_tk;
    protected $_preferences;

    /**
     * @param ARI  $accounts
     * @param TJRI $journals
     */
    public function __construct(ARI $accounts, TJRI $journals, PHI $preferences, tk $toolkit)
    {
        $this->_accounts = $accounts;
        $this->_journals = $journals;
        $this->_preferences = $preferences;
        $this->_tk = $toolkit;
    }

    /**
     * @param null $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeAccount($accountId = null)
    {
        list($start, $end) = $this->_tk->getDateRange();
        \Log::debug('Start is (cannot clone?): ' . $start);
        $current = clone $start;
        $return = [];
        $account = null;
        $today = new Carbon\Carbon;

        if (!is_null($accountId)) {
            /** @var \Account $account */
            $account = $this->_accounts->find($accountId);
        }

        if (is_null($account)) {

            $pref = $this->_preferences->get('frontpageAccounts', []);
            if ($pref->data == []) {
                $accounts = $this->_accounts->getActiveDefault();
            } else {
                $accounts = $this->_accounts->getByIds($pref->data);
            }
            foreach ($accounts as $account) {
                $return[] = ['name' => $account->name, 'id' => 'acc-' . $account->id, 'data' => []];

            }
            while ($current <= $end) {
                // loop accounts:
                foreach ($accounts as $index => $account) {


                    if ($current > $today) {
                        $return[$index]['data'][] = [$current->timestamp * 1000, $account->predict(clone $current)];
                    } else {
                        $return[$index]['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
                    }
                }
                $current->addDay();
            }
        } else {
            $return[0] = ['name' => $account->name, 'id' => $account->id, 'data' => []];
            \Log::debug('Start is: '.$start);
            \Log::debug('End is: '.$end);
            while ($current <= $end) {
                \Log::debug('Current: ' . $current.' is smaller or equal to ' . $end);
                if ($current > $today) {
                    $return[0]['data'][] = [$current->timestamp * 1000, $account->predict(clone $current)];
                } else {
                    $return[0]['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
                }

                $current->addDay();
            }
        }
//        // add an error bar as experiment:
//        foreach($return as $index => $serie) {
//            $err = [
//                'type' => 'errorbar',
//                'name' => $serie['name'].' pred',
//                'linkedTo' => $serie['id'],
//                'data' => []
//            ];
//            foreach($serie['data'] as $entry) {
//                $err['data'][] = [$entry[0],10,300];
//            }
//            $return[] = $err;
//        }


        return Response::json($return);
    }

    /**
     * Return some beneficiary info for an account and a date.
     *
     * @param $name
     * @param $day
     * @param $month
     * @param $year
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

    public function homeCategories()
    {
        list($start, $end) =$this->_tk->getDateRange();
        $account = null;
        $result = [];
        // grab all transaction journals in this period:
        $journals = $this->_journals->getByDateRange($start, $end);

        $result = [];
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
}