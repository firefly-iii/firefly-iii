<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 27/09/14
 * Time: 07:39
 */

namespace Firefly\Helper\Controllers;

use LaravelBook\Ardent\Builder;

/**
 * Class Json
 *
 * @package Firefly\Helper\Controllers
 */
class Json implements JsonInterface
{
    /**
     * Grabs all the parameters entered by the DataTables JQuery plugin and creates
     * a nice array to be used by the other methods. It's also cleaning up and what-not.
     *
     * @return array
     */
    public function dataTableParameters()
    {
        /*
         * Process all parameters!
         */
        if (intval(\Input::get('length')) < 0) {
            $length = 10000; // we get them all if no length is defined.
        } else {
            $length = intval(\Input::get('length'));
        }
        $parameters = [
            'start'  => intval(\Input::get('start')),
            'length' => $length,
            'draw'   => intval(\Input::get('draw')),
        ];


        /*
         * Columns:
         */
        if (!is_null(\Input::get('columns')) && is_array(\Input::get('columns'))) {
            foreach (\Input::get('columns') as $column) {
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
        if (!is_null(\Input::get('order')) && is_array(\Input::get('order'))) {
            foreach (\Input::get('order') as $order) {
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
        $parameters['search'] = [
            'value' => '',
            'regex' => false
        ];
        if (!is_null(\Input::get('search')) && is_array(\Input::get('search'))) {
            $search               = \Input::get('search');
            $parameters['search'] = [
                'value' => $search['value'],
                'regex' => $search['regex'] == 'true' ? true : false
            ];
        }
        return $parameters;
    }

    /**
     * Do some sorting, counting and ordering on the query and return a nicely formatted array
     * that can be used by the DataTables JQuery plugin.
     *
     * @param array   $parameters
     * @param Builder $query
     *
     * @return array
     */
    public function journalDataset(array $parameters, Builder $query)
    {
        /*
         * Count query:
         */
        $count = $query->count();

        /*
         * Update the selection:
         */

        $query->take($parameters['length']);
        if ($parameters['start'] > 0) {
            $query->skip($parameters['start']);
        }

        /*
         * Input search parameters:
         */
        $filtered = $count;
        if (strlen($parameters['search']['value']) > 0) {
            $query->where('transaction_journals.description', 'LIKE', '%' . e($parameters['search']['value']) . '%');
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
     * Builds most of the query required to grab transaction journals from the database.
     * This is useful because all three pages showing different kinds of transactions use
     * the exact same query with only slight differences.
     *
     * @param array $parameters
     *
     * @return Builder
     */
    public function journalQuery(array $parameters)
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
        $query->where('user_id', \Auth::user()->id);
        $query->where('completed', 1);
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
     * Do some sorting, counting and ordering on the query and return a nicely formatted array
     * that can be used by the DataTables JQuery plugin.
     *
     * @param array   $parameters
     * @param Builder $query
     *
     * @return array
     */
    public function recurringTransactionsDataset(array $parameters, Builder $query)
    {
        /*
 * Count query:
 */
        $count = $query->count();

        /*
         * Update the selection:
         */

        $query->take($parameters['length']);
        if ($parameters['start'] > 0) {
            $query->skip($parameters['start']);
        }

        /*
         * Input search parameters:
         */
        $filtered = $count;
        if (strlen($parameters['search']['value']) > 0) {
            $query->where('recurring_transactions.description', 'LIKE', '%' . e($parameters['search']['value']) . '%');
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
        /** @var Collection $set */
        $set = $query->get(
            [
                'recurring_transactions.*',
            ]
        );

        /*
         * Loop set and create entries to return.
         */
        foreach ($set as $entry) {
            $data['data'][] = [

                'name'        => ['name' => $entry->name,'url' => route('recurring.show',$entry->id)],
                'match'       => explode(' ',$entry->match),
                'amount_max'  => floatval($entry->amount_max),
                'amount_min'  => floatval($entry->amount_min),
                'date'        => $entry->date->format('j F Y'),
                'active'      => intval($entry->active),
                'automatch'   => intval($entry->automatch),
                'repeat_freq' => $entry->repeat_freq,
                'id'          => [
                    'edit'   => route('recurring.edit', $entry->id),
                    'delete' => route('recurring.delete', $entry->id)
                ]
            ];

        }
        return $data;
    }

    /**
     * Create a query that will pick up all recurring transactions from the database.
     *
     * @param array $parameters
     *
     * @return Builder
     */
    public function recurringTransactionsQuery(array $parameters)
    {
        $query = \RecurringTransaction::where('user_id', \Auth::user()->id);

        if (isset($parameters['order']) && count($parameters['order']) > 0) {
            foreach ($parameters['order'] as $order) {
                $query->orderBy($order['name'], $order['dir']);
            }
        } else {
            $query->orderBy('name', 'ASC');
        }
        return $query;
    }
} 