<?php

use Firefly\Helper\Controllers\JsonInterface as JI;
use Illuminate\Support\Collection;
use LaravelBook\Ardent\Builder;

/**
 * Class JsonController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class JsonController extends BaseController
{
    /** @var \Firefly\Helper\Controllers\JsonInterface $helper */
    protected $helper;

    public function __construct(JI $helper)
    {
        $this->helper = $helper;


    }

    /**
     * Returns a list of categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        /** @var \Firefly\Storage\Category\EloquentCategoryRepository $categories */
        $categories = App::make('Firefly\Storage\Category\CategoryRepositoryInterface');
        $list       = $categories->get();
        $return     = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);


    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts()
    {
        /** @var \Firefly\Storage\Account\EloquentAccountRepository $accounts */
        $accounts = App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $list     = $accounts->getOfTypes(['Expense account', 'Beneficiary account']);
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Returns a list of transactions, expenses only, using the given parameters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenses()
    {

        /*
         * Gets most parameters from the Input::all() array:
         */
        $parameters = $this->helper->dataTableParameters();

        /*
         * Add some more parameters to fine tune the query:
         */
        $parameters['transactionTypes'] = ['Withdrawal'];
        $parameters['amount']           = 'negative';

        /*
         * Get the query:
         */
        $query = $this->helper->journalQuery($parameters);

        /*
         * Build result set:
         */
        $resultSet = $this->helper->journalDataset($parameters, $query);


        /*
         * Build return data:
         */
        return Response::json($resultSet);
    }

    /**
     *
     */
    public function recurringjournals(RecurringTransaction $recurringTransaction)
    {
        $parameters                     = $this->helper->dataTableParameters();
        $parameters['transactionTypes'] = ['Withdrawal'];
        $parameters['amount']           = 'negative';

        $query = $this->helper->journalQuery($parameters);

        $query->where('recurring_transaction_id', $recurringTransaction->id);
        $resultSet = $this->helper->journalDataset($parameters, $query);


        /*
         * Build return data:
         */
        return Response::json($resultSet);
    }

    public function recurring()
    {
        $parameters = $this->helper->dataTableParameters();
        $query      = $this->helper->recurringTransactionsQuery($parameters);
        $resultSet  = $this->helper->recurringTransactionsDataset($parameters, $query);
        return Response::json($resultSet);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function revenue()
    {
        $parameters                     = $this->helper->dataTableParameters();
        $parameters['transactionTypes'] = ['Deposit'];
        $parameters['amount']           = 'positive';

        $query     = $this->helper->journalQuery($parameters);
        $resultSet = $this->helper->journalDataset($parameters, $query);


        /*
         * Build return data:
         */
        return Response::json($resultSet);
    }

    /**
     * Returns a JSON list of all revenue accounts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts()
    {
        /** @var \Firefly\Storage\Account\EloquentAccountRepository $accounts */
        $accounts = App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $list     = $accounts->getOfTypes(['Revenue account']);
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Returns a list of all transfers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transfers()
    {
        $parameters                     = $this->helper->dataTableParameters();
        $parameters['transactionTypes'] = ['Transfer'];
        $parameters['amount']           = 'positive';

        $query     = $this->helper->journalQuery($parameters);
        $resultSet = $this->helper->journalDataset($parameters, $query);


        /*
         * Build return data:
         */
        return Response::json($resultSet);
    }
} 