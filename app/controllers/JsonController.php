<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\Component\ComponentRepositoryInterface as CRI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;
use Illuminate\Support\Collection;

/**
 * Class JsonController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class JsonController extends BaseController
{
    protected $_accounts;
    protected $_components;
    protected $_categories;
    protected $_budgets;
    /** @var TJRI $_journals */
    protected $_journals;

    /**
     * @param ARI  $accounts
     * @param CRI  $components
     * @param Cat  $categories
     * @param Bud  $budgets
     * @param TJRI $journals
     */
    public function __construct(ARI $accounts, CRI $components, Cat $categories, Bud $budgets, TJRI $journals)
    {
        $this->_components = $components;
        $this->_accounts   = $accounts;
        $this->_categories = $categories;
        $this->_budgets    = $budgets;
        $this->_journals   = $journals;
    }

    /**
     * Returns a list of transactions, expenses only, using the given parameters.
     */
    public function expenses()
    {


        /*
         * Process all parameters!
         */
        $parameters = [
            'start'  => intval(Input::get('start')),
            'length' => intval(Input::get('length')),
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
        if (!is_null(Input::get('order')) && is_array(Input::get('order'))) {
            foreach (Input::get('order') as $order) {
                $columnIndex           = intval($order['column']);
                $columnName            = $parameters['columns'][$columnIndex]['name'];
                $parameters['order'][] = [
                    'name' => $columnName,
                    'dir'  => strtoupper($order['dir'])
                ];
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

        /*
         * Build query:
         */
        $query = \TransactionJournal::lessThan(0)->transactionTypes(['Withdrawal'])->withRelevantData();
        $count = $query->count();
        $query->take($parameters['length']);
        $query->skip($parameters['start']);

        /*
         * Add sort parameters to query:
         */
        \Debugbar::startMeasure('order');
        if (isset($parameters['order']) && count($parameters['order']) > 0) {
            foreach ($parameters['order'] as $order) {
                $query->orderBy($order['name'], $order['dir']);
            }
        } else {
            $query->defaultSorting();
        }

        /*
         * Build return array:
         */
        $data = [
            'draw'            => $parameters['draw'],
            'recordsTotal'    => $count,
            'recordsFiltered' => $count,
            'data'            => [],

        ];

        /*
         * Get paginated result set:
         */
        /** @var Collection $set */
        $set = $query->get(['transaction_journals.*', 'transactions.amount']);

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

        /*
         * Build return data:
         */
        $return = $data;

        if (Input::get('debug') == 'true') {
            echo '<pre>';
            print_r($parameters);
            echo '<hr>';
            print_r($return);
            Response::json($return);

        } else {
            return Response::json($return);
        }
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