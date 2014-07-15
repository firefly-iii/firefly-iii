<?php

use Firefly\Helper\Toolkit\Toolkit as tk;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

class ChartController extends BaseController
{

    protected $accounts;
    protected $journals;

    public function __construct(ARI $accounts, TJRI $journals)
    {
        $this->accounts = $accounts;
        $this->journals = $journals;
    }

    /**
     * Show home charts.
     */
    public function homeAccount($account = null)
    {
        list($start, $end) = tk::getDateRange();
        $current = clone $start;
        $return = [];

        if (is_null($account)) {
            $accounts = $this->accounts->getActiveDefault();

            foreach ($accounts as $index => $account) {
                $return[] = ['name' => $account->name, 'data' => []];
            }
            while ($current <= $end) {

                // loop accounts:
                foreach ($accounts as $index => $account) {
                    $return[$index]['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
                }
                $current->addDay();
            }
        } else {
            // do something experimental:
            $account = $this->accounts->find($account);
            if (is_null($account)) {
                return View::make('error')->with('message', 'No account found.');
            }
            $return[0] = ['name' => $account->name, 'data' => []];


            while ($current <= $end) {

                $return[0]['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
                $current->addDay();
            }

        }
        return Response::json($return);
    }

    /**
     * Get all budgets used in transaction(journals) this period:
     */
    public function homeBudgets()
    {
        list($start, $end) = tk::getDateRange();
        $data = [
            'type' => 'pie',
            'name' => 'Expense: ',
            'data' => []
        ];

        $result = $this->journals->homeBudgetChart($start, $end);

        foreach ($result as $name => $amount) {
            $data['data'][] = [$name, $amount];
        }
        return Response::json([$data]);

    }

    /**
     * Get all categories used in transaction(journals) this period.
     */
    public function homeCategories()
    {
        list($start, $end) = tk::getDateRange();

        $result = $this->journals->homeCategoryChart($start, $end);
        $data = [
            'type' => 'pie',
            'name' => 'Amount: ',
            'data' => []
        ];

        foreach ($result as $name => $amount) {
            $data['data'][] = [$name, $amount];
        }
        return Response::json([$data]);

    }

    /**
     * get all beneficiaries used in transaction(journals) this period.
     */
    public function homeBeneficiaries()
    {
        list($start, $end) = tk::getDateRange();
        $data = [
            'type' => 'pie',
            'name' => 'Amount: ',
            'data' => []
        ];

        $result = $this->journals->homeBeneficiaryChart($start, $end);

        foreach ($result as $name => $amount) {
            $data['data'][] = [$name, $amount];
        }
        return Response::json([$data]);

    }
} 