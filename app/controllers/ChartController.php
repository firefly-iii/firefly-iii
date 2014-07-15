<?php

use Firefly\Helper\Toolkit\Toolkit as tk;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class ChartController
 */
class ChartController extends BaseController
{

    protected $_accounts;
    protected $_journals;

    /**
     * @param ARI  $accounts
     * @param TJRI $journals
     */
    public function __construct(ARI $accounts, TJRI $journals)
    {
        $this->_accounts = $accounts;
        $this->_journals = $journals;
    }

    /**
     * @param null $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeAccount($accountId = null)
    {
        list($start, $end) = tk::getDateRange();
        $current = clone $start;
        $return = [];
        $account = null;

        if (!is_null($accountId)) {
            $account = $this->_accounts->find($accountId);
        }

        if (is_null($account)) {
            $accounts = $this->_accounts->getActiveDefault();

            foreach ($accounts as $account) {
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

        $result = $this->_journals->homeBudgetChart($start, $end);

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

        $result = $this->_journals->homeCategoryChart($start, $end);
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

        $result = $this->_journals->homeBeneficiaryChart($start, $end);

        foreach ($result as $name => $amount) {
            $data['data'][] = [$name, $amount];
        }
        return Response::json([$data]);

    }
} 