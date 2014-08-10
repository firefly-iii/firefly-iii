<?php

namespace Firefly\Helper\Controllers;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class Budget
 *
 * @package Firefly\Helper\Controllers
 */
class Budget implements BudgetInterface
{

    /**
     * @param Collection $budgets
     *
     * @return mixed|void
     */
    public function organizeByDate(Collection $budgets)
    {
        $return = [];

        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {

                foreach ($limit->limitrepetitions as $rep) {
                    $periodOrder = $rep->periodOrder();
                    $period = $rep->periodShow();
                    $return[$periodOrder] = isset($return[$periodOrder])
                        ? $return[$periodOrder]
                        : ['date'      => $period,
                           'budget_id' => $limit->budget_id];

                }
            }
        }
        // put all the budgets under their respective date:
        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {
                foreach ($limit->limitrepetitions as $rep) {
                    $rep->left = $rep->left();

                    $month = $rep->periodOrder();
                    $return[$month]['limitrepetitions'][] = $rep;
                }
            }
        }
        krsort($return);

        return $return;
    }

    /**
     * @param         $repetitionId
     *
     * @return array
     */
    public function organizeRepetition($repetitionId)
    {
        $result = [];
        $repetition = \LimitRepetition::with('limit', 'limit.budget')->leftJoin(
            'limits', 'limit_repetitions.limit_id', '=', 'limits.id'
        )->leftJoin('components', 'limits.component_id', '=', 'components.id')->where(
                'components.user_id', \Auth::user()->id
            )
            ->where('limit_repetitions.id', $repetitionId)->first(['limit_repetitions.*']);

        // get transactions:
        $set = $repetition->limit->budget->transactionjournals()->with(
            'transactions', 'transactions.account', 'components', 'transactiontype'
        )->leftJoin(
                'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
            )->where('transaction_types.type', 'Withdrawal')->where(
                'date', '>=', $repetition->startdate->format('Y-m-d')
            )->where('date', '<=', $repetition->enddate->format('Y-m-d'))->orderBy('date', 'DESC')->orderBy(
                'id', 'DESC'
            )->get(['transaction_journals.*']);

        $result[0] = [
            'date'            => $repetition->periodShow(),
            'limit'           => $repetition->limit,
            'limitrepetition' => $repetition,
            'journals'        => $set,
            'paginated'       => false
        ];

        return $result;
    }

    /**
     * @param \Budget $budget
     *
     * @return mixed|void
     */
    public function organizeRepetitions(\Budget $budget)
    {
        $result = [];
        $inRepetition = [];
        foreach ($budget->limits as $limit) {
            foreach ($limit->limitrepetitions as $repetition) {
                $order = $repetition->periodOrder();
                $result[$order] = [
                    'date'            => $repetition->periodShow(),
                    'limitrepetition' => $repetition,
                    'limit'           => $limit,
                    'journals'        => [],
                    'paginated'       => false
                ];
                $transactions = [];
                $set = $budget->transactionjournals()->with(
                    'transactions', 'transactions.account', 'components', 'transactiontype'
                )->leftJoin(
                        'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                    )->where('transaction_types.type', 'Withdrawal')->where(
                        'date', '>=', $repetition->startdate->format('Y-m-d')
                    )->where('date', '<=', $repetition->enddate->format('Y-m-d'))->orderBy('date', 'DESC')->orderBy(
                        'id', 'DESC'
                    )->get(['transaction_journals.*']);
                foreach ($set as $entry) {
                    $transactions[] = $entry;
                    $inRepetition[] = $entry->id;
                }
                $result[$order]['journals'] = $transactions;
            }

        }

        if (count($inRepetition) > 0) {
            $query = $budget->transactionjournals()->with(
                'transactions', 'transactions.account', 'components', 'transactiontype',
                'transactions.account.accounttype'
            )->whereNotIn(
                    'transaction_journals.id', $inRepetition
                )->orderBy('date', 'DESC')->orderBy(
                    'transaction_journals.id', 'DESC'
                );
        } else {
            $query = $budget->transactionjournals()->with(
                'transactions', 'transactions.account', 'components', 'transactiontype',
                'transactions.account.accounttype'
            )->orderBy('date', 'DESC')->orderBy(
                    'transaction_journals.id', 'DESC'
                );
        }

        // build paginator:
        $perPage = 25;
        $totalItems = $query->count();
        $page = intval(\Input::get('page')) > 1 ? intval(\Input::get('page')) : 1;
        $skip = ($page - 1) * $perPage;
        $set = $query->skip($skip)->take($perPage)->get();

        // stupid paginator!
        $items = [];
        /** @var $item \TransactionJournal */
        foreach ($set as $item) {
            $items[] = $item;
        }
        $paginator = \Paginator::make($items, $totalItems, $perPage);
        $result['0000'] = ['date'     => 'Not in an envelope', 'limit' => null, 'paginated' => true,
                           'journals' => $paginator];

        krsort($result);

        return $result;
    }

    /**
     * @param \Budget $budget
     *
     * @return mixed|void
     */
    public function outsideRepetitions(\Budget $budget)
    {
        $inRepetitions = [];
        foreach ($budget->limits as $limit) {
            foreach ($limit->limitrepetitions as $repetition) {
                $set = $budget->transactionjournals()->leftJoin(
                    'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                )->where('transaction_types.type', 'Withdrawal')->where(
                        'date', '>=', $repetition->startdate->format('Y-m-d')
                    )->where('date', '<=', $repetition->enddate->format('Y-m-d'))->orderBy('date', 'DESC')->get(
                        ['transaction_journals.id']
                    );
                foreach ($set as $item) {
                    $inRepetitions[] = $item->id;
                }
            }

        }

        $query = $budget->transactionjournals()->with(
            'transactions', 'transactions.account', 'components', 'transactiontype',
            'transactions.account.accounttype'
        )->whereNotIn(
                'transaction_journals.id', $inRepetitions
            )->orderBy('date', 'DESC')->orderBy(
                'transaction_journals.id', 'DESC'
            );

        // build paginator:
        $perPage = 25;
        $totalItems = $query->count();
        $page = intval(\Input::get('page')) > 1 ? intval(\Input::get('page')) : 1;
        $skip = ($page - 1) * $perPage;
        $set = $query->skip($skip)->take($perPage)->get();

        // stupid paginator!
        $items = [];
        /** @var $item \TransactionJournal */
        foreach ($set as $item) {
            $items[] = $item;
        }
        $paginator = \Paginator::make($items, $totalItems, $perPage);
        $result = [0 => ['date'     => 'Not in an envelope', 'limit' => null, 'paginated' => true,
                         'journals' => $paginator]];

        return $result;
    }
}