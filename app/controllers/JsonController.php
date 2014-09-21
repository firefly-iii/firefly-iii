<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;
use Illuminate\Support\Collection;
use LaravelBook\Ardent\Builder;

/**
 * Class JsonController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class JsonController extends BaseController
{
    protected $_accounts;
    protected $_categories;
    protected $_budgets;
    /** @var TJRI $_journals */
    protected $_journals;

    /**
     * @param ARI  $accounts
     * @param Cat  $categories
     * @param Bud  $budgets
     * @param TJRI $journals
     */
    public function __construct(ARI $accounts, Cat $categories, Bud $budgets, TJRI $journals)
    {
        $this->_accounts   = $accounts;
        $this->_categories = $categories;
        $this->_budgets    = $budgets;
        $this->_journals   = $journals;
    }

    /**
     *
     */
    public function revenue()
    {
        $parameters                     = $this->_datatableParameters();
        $parameters['transactionTypes'] = ['Deposit'];
        $parameters['amount']           = 'positive';

        $query = $this->_datatableQuery($parameters);
        $resultSet = $this->_datatableResultset($parameters, $query);


        /*
         * Build return data:
         */

        if (Input::get('debug') == 'true') {
            echo '<pre>';
            print_r($parameters);
            echo '<hr>';
            print_r($resultSet);
            return '';

        } else {
            return Response::json($resultSet);
        }


    }

    /**
     *
     */
    public function transfers()
    {
        $parameters                     = $this->_datatableParameters();
        $parameters['transactionTypes'] = ['Transfer'];
        $parameters['amount']           = 'positive';

        $query = $this->_datatableQuery($parameters);
        $resultSet = $this->_datatableResultset($parameters, $query);


        /*
         * Build return data:
         */

        if (Input::get('debug') == 'true') {
            echo '<pre>';
            print_r($parameters);
            echo '<hr>';
            print_r($resultSet);
            return '';

        } else {
            return Response::json($resultSet);
        }


    }

    /**
     * @return array
     */
    protected function _datatableParameters()
    {
        /*
         * Process all parameters!
         */
        $parameters = [
            'start'  => intval(Input::get('start')),
            'length' => intval(Input::get('length')) < 0 ? 100000 : intval(Input::get('length')),
            'draw'   => intval(Input::get('draw')),
        ];


        /*
         * Columns:
         */
        if (!is_null(Input::get('columns')) && is_array(Input::get('columns'))) {
            foreach (Input::get('columns') as $column) {
                $parameters['columns'][] = [
                    'data'       => $column['data'],
                    'name'       => $column['name'],
                    'searchable' => $column['searchable'] == 'true' ? true : false,
                    'orderable'  => $column['orderable'] == 'true' ? true : false,
                    'search'     => [
                        'value' => $column['search']['value'],
                        'regex' => $column['search']['regex'] == 'true' ? true : false,
                    ]
                ];
            }
        }


        /*
         * Sorting.
         */
        $parameters['orderOnAccount'] = false;
        if (!is_null(Input::get('order')) && is_array(Input::get('order'))) {
            foreach (Input::get('order') as $order) {
                $columnIndex           = intval($order['column']);
                $columnName            = $parameters['columns'][$columnIndex]['name'];
                $parameters['order'][] = [
                    'name' => $columnName,
                    'dir'  => strtoupper($order['dir'])
                ];
                if ($columnName == 'to' || $columnName == 'from') {
                    $parameters['orderOnAccount'] = true;
                }
            }
        }
        /*
         * Search parameters:
         */
        if (!is_null(Input::get('search')) && is_array(Input::get('search'))) {
            $search               = Input::get('search');
            $parameters['search'] = [
                'value' => $search['value'],
                'regex' => $search['regex'] == 'true' ? true : false
            ];
        }
        return $parameters;
    }

    /**
     * @param array $parameters
     *
     * @return Builder
     */
    protected function _datatableQuery(array $parameters)
    {
        /*
         * We need the following vars to fine tune the query:
         */
        if ($parameters['amount'] == 'negative') {
            $operator        = '<';
            $operatorNegated = '>';
            $function        = 'lessThan';
        } else {
            $operator        = '>';
            $operatorNegated = '<';
            $function        = 'moreThan';
        }

        /*
         * Build query:
         */
        $query = \TransactionJournal::transactionTypes($parameters['transactionTypes'])->withRelevantData();

        /*
         * This is complex. Join `transactions` twice, once for the "to" account and once for the
         * "from" account. Then get the amount from one of these (depends on type).
         *
         * Only need to do this when there's a sort order for "from" or "to".
         *
         * Also need the table prefix for this to work.
         */
        if ($parameters['orderOnAccount'] === true) {
            $connection = \Config::get('database.default');
            $prefix     = \Config::get('database.connections.' . $connection . '.prefix');
            // left join first table for "from" account:
            $query->leftJoin(
                'transactions AS ' . $prefix . 't1', function ($join) use ($operator) {
                    $join->on('t1.transaction_journal_id', '=', 'transaction_journals.id')
                        ->on('t1.amount', $operator, \DB::Raw(0));
                }
            );
            // left join second table for "to" account:
            $query->leftJoin(
                'transactions AS ' . $prefix . 't2', function ($join) use ($operatorNegated) {
                    $join->on('t2.transaction_journal_id', '=', 'transaction_journals.id')
                        ->on('t2.amount', $operatorNegated, \DB::Raw(0));
                }
            );

            // also join accounts twice to get the account's name, which we need for sorting.
            $query->leftJoin('accounts as ' . $prefix . 'a1', 'a1.id', '=', 't1.account_id');
            $query->leftJoin('accounts as ' . $prefix . 'a2', 'a2.id', '=', 't2.account_id');
        } else {
            // less complex
            $query->$function(0);
        }

        /*
         * Add sort parameters to query:
         */
        if (isset($parameters['order']) && count($parameters['order']) > 0) {
            foreach ($parameters['order'] as $order) {
                $query->orderBy($order['name'], $order['dir']);
            }
        } else {
            $query->defaultSorting();
        }
        return $query;
    }

    /**
     * Returns a list of transactions, expenses only, using the given parameters.
     */
    public function expenses()
    {

        /*
         * Gets most parameters from the Input::all() array:
         */
        $parameters = $this->_datatableParameters();

        /*
         * Add some more parameters to fine tune the query:
         */
        $parameters['transactionTypes'] = ['Withdrawal'];
        $parameters['amount']           = 'negative';

        /*
         * Get the query:
         */
        $query = $this->_datatableQuery($parameters);

        /*
         * Build result set:
         */
        $resultSet = $this->_datatableResultset($parameters, $query);


        /*
         * Build return data:
         */

        if (Input::get('debug') == 'true') {
            echo '<pre>';
            print_r($parameters);
            echo '<hr>';
            print_r($resultSet);
            return '';

        } else {
            return Response::json($resultSet);
        }
    }

    protected function _datatableResultset(array $parameters, Builder $query)
    {
        /*
         * Count query:
         */
        $count = $query->count();

        /*
         * Update the selection:
         */

        $query->take($parameters['length']);
        $query->skip($parameters['start']);

        /*
         * Input search parameters:
         */
        $filtered = $count;
        if(strlen($parameters['search']['value']) > 0) {
            $query->where('transaction_journals.description','LIKE','%'.e($parameters['search']['value']).'%');
            $filtered = $query->count();
        }


        /*
         * Build return array:
         */
        $data = [
            'draw'            => $parameters['draw'],
            'recordsTotal'    => $count,
            'recordsFiltered' => $filtered,
            'data'            => [],

        ];

        /*
         * Get paginated result set:
         */
        if ($parameters['orderOnAccount'] === true) {
            /** @var Collection $set */
            $set = $query->get(
                [
                    'transaction_journals.*',
                    't1.amount',
                    't1.account_id AS from_id',
                    'a1.name AS from',
                    't2.account_id AS to_id',
                    'a2.name AS to',
                ]
            );
        } else {
            /** @var Collection $set */
            $set = $query->get(
                [
                    'transaction_journals.*',
                    'transactions.amount',
                ]
            );
        }

        /*
         * Loop set and create entries to return.
         */
        foreach ($set as $entry) {
            $from           = $entry->transactions[0]->account;
            $to             = $entry->transactions[1]->account;
            $data['data'][] = [
                'date'        => $entry->date->format('j F Y'),
                'description' => [
                    'description' => $entry->description,
                    'url'         => route('transactions.show', $entry->id)
                ],
                'amount'      => floatval($entry->amount),
                'from'        => ['name' => $from->name, 'url' => route('accounts.show', $from->id)],
                'to'          => ['name' => $to->name, 'url' => route('accounts.show', $to->id)],
                'id'          => [
                    'edit'   => route('transactions.edit', $entry->id),
                    'delete' => route('transactions.delete', $entry->id)
                ]
            ];

        }
        return $data;
    }

    /**
     * Returns a JSON list of all beneficiaries.
     */
    public function expenseAccounts()
    {
        $list   = $this->_accounts->getOfTypes(['Expense account', 'Beneficiary account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Returns a JSON list of all revenue accounts.
     */
    public function revenueAccounts()
    {
        $list   = $this->_accounts->getOfTypes(['Revenue account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Responds some JSON for typeahead fields.
     */
    public function categories()
    {
        $list   = $this->_categories->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);


    }
} 