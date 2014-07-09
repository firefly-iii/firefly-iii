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

        // chart
        $chart = App::make('gchart');
        $chart->addColumn('Day of the month', 'date');


        if (is_null($account)) {
            // get accounts:
            $accounts = $this->accounts->getActiveDefault();

            foreach ($accounts as $account) {
                $chart->addColumn($account->name, 'number');
            }

            while ($current <= $end) {
                $row = [clone $current];

                // loop accounts:
                foreach ($accounts as $account) {
                    $row[] = $account->balance(clone $current);
                }
                $current->addDay();
                $chart->addRowArray($row);
            }
        } else {
            $account = $this->accounts->find($account);
            if (is_null($account)) {
                return View::make('error')->with('message', 'No account found.');
            }
            $chart->addColumn($account->name, 'number');
            while ($current <= $end) {
                $row = [clone $current, $account->balance(clone $current)];
                $current->addDay();
                $chart->addRowArray($row);
            }
        }

        $chart->generate();
        return $chart->getData();
    }

    /**
     * Get all budgets used in transaction(journals) this period:
     */
    public function homeBudgets()
    {
        list($start, $end) = tk::getDateRange();

        $result = $this->journals->homeBudgetChart($start, $end);

        // create a chart:
        $chart = App::make('gchart');
        $chart->addColumn('Budget', 'string');
        $chart->addColumn('Amount', 'number');
        foreach ($result as $name => $amount) {
            $chart->addRow($name, $amount);
        }
        $chart->generate();
        return Response::json($chart->getData());

    }

    /**
     * Get all categories used in transaction(journals) this period.
     */
    public function homeCategories()
    {
        list($start, $end) = tk::getDateRange();

        $result = $this->journals->homeCategoryChart($start, $end);

        // create a chart:
        $chart = App::make('gchart');
        $chart->addColumn('Category', 'string');
        $chart->addColumn('Amount', 'number');
        foreach ($result as $name => $amount) {
            $chart->addRow($name, $amount);
        }
        $chart->generate();
        return Response::json($chart->getData());

    }

    /**
     * get all beneficiaries used in transaction(journals) this period.
     */
    public function homeBeneficiaries()
    {
        list($start, $end) = tk::getDateRange();

        $result = $this->journals->homeBeneficiaryChart($start, $end);

        // create a chart:
        $chart = App::make('gchart');
        $chart->addColumn('Beneficiary', 'string');
        $chart->addColumn('Amount', 'number');
        foreach ($result as $name => $amount) {
            $chart->addRow($name, $amount);
        }
        $chart->generate();
        return Response::json($chart->getData());

    }
} 