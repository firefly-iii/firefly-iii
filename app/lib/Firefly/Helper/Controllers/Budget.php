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
     * First, loop all budgets, all of their limits and all repetitions to get an overview per period
     * and some basic information about that repetition's data.
     *
     *
     *
     * @param Collection $budgets
     *
     * @return mixed|void
     */
    public function organizeByDate(Collection $budgets)
    {
        $return = [];

        /** @var \Budget $budget */
        foreach ($budgets as $budget) {

            /** @var \Limit $limit */
            foreach ($budget->limits as $limit) {

                /** @var \LimitRepetition $repetition */
                foreach ($limit->limitrepetitions as $repetition) {
                    $repetition->left = $repetition->left();
                    $periodOrder      = $repetition->periodOrder();
                    $period           = $repetition->periodShow();
                    if (!isset($return[$periodOrder])) {

                        $return[$periodOrder] = [
                            'date'             => $period,
                            'start'            => $repetition->startdate,
                            'end'              => $repetition->enddate,
                            'budget_id'        => $budget->id,
                            'limitrepetitions' => [$repetition]
                        ];
                    } else {
                        $return[$periodOrder]['limitrepetitions'][] = $repetition;
                    }

                }
            }
        }
        krsort($return);

        return $return;
    }

    /**
     * Get a repetition (complex because of user check)
     * and then get the transactions in it.
     * @param         $repetitionId
     *
     * @return array
     */
    public function organizeRepetition(\LimitRepetition $repetition)
    {
        $result = [];
        // get transactions:
        $set = $repetition->limit->budget
            ->transactionjournals()
            ->withRelevantData()
            ->transactionTypes(['Withdrawal'])
            ->after($repetition->startdate)
            ->before($repetition->enddate)
            ->defaultSorting()
            ->get(['transaction_journals.*']);

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
     *
     *
     * @param \Budget $budget
     * @param bool $useSessionDates
     *
     * @return array|mixed
     * @throws \Firefly\Exception\FireflyException
     */
    public function organizeRepetitions(\Budget $budget, $useSessionDates = false)
    {
        $sessionStart = \Session::get('start');
        $sessionEnd   = \Session::get('end');

        $result       = [];
        $inRepetition = [];

        // get the limits:
        if ($useSessionDates) {
            $limits = $budget->limits()->where('startdate', '>=', $sessionStart->format('Y-m-d'))->where(
                             'startdate', '<=', $sessionEnd->format('Y-m-d')
            )->get();
        } else {
            $limits = $budget->limits;
        }

        /** @var \Limit $limit */
        foreach ($limits as $limit) {
            foreach ($limit->limitrepetitions as $repetition) {
                $order          = $repetition->periodOrder();
                $result[$order] = [
                    'date'            => $repetition->periodShow(),
                    'limitrepetition' => $repetition,
                    'limit'           => $limit,
                    'journals'        => [],
                    'paginated'       => false
                ];
                $transactions   = [];
                $set            = $budget->transactionjournals()
                                         ->withRelevantData()
                                         ->transactionTypes(['Withdrawal'])
                                         ->after($repetition->startdate)
                                         ->before($repetition->enddate)
                                         ->defaultSorting()
                                         ->get(['transaction_journals.*']);
                foreach ($set as $entry) {
                    $transactions[] = $entry;
                    $inRepetition[] = $entry->id;
                }
                $result[$order]['journals'] = $transactions;
            }

        }
        if ($useSessionDates === false) {
            $query = $budget->transactionjournals()->withRelevantData()->defaultSorting();
            if (count($inRepetition) > 0) {
                $query->whereNotIn('transaction_journals.id', $inRepetition);
            }

            // build paginator:
            $perPage    = 25;
            $totalItems = $query->count();
            $page       = intval(\Input::get('page')) > 1 ? intval(\Input::get('page')) : 1;
            $skip       = ($page - 1) * $perPage;
            $set        = $query->skip($skip)->take($perPage)->get();

            // stupid paginator!
            $items = [];
            /** @var $item \TransactionJournal */
            foreach ($set as $item) {
                $items[] = $item;
            }
            $paginator      = \Paginator::make($items, $totalItems, $perPage);
            $result['0000'] = [
                'date'      => 'Not in an envelope',
                'limit'     => null,
                'paginated' => true,
                'journals'  => $paginator];
        }
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
                $set = $budget->transactionjournals()
                              ->transactionTypes(['Withdrawal'])
                              ->after($repetition->startdate)
                              ->before($repetition->enddate)
                              ->defaultSorting()
                              ->get(['transaction_journals.id']);
                foreach ($set as $item) {
                    $inRepetitions[] = $item->id;
                }
            }

        }

        $query = $budget->transactionjournals()
                        ->withRelevantData()
                        ->whereNotIn('transaction_journals.id', $inRepetitions)
                        ->defaultSorting();

        // build paginator:
        $perPage    = 25;
        $totalItems = $query->count();
        $page       = intval(\Input::get('page')) > 1 ? intval(\Input::get('page')) : 1;
        $skip       = ($page - 1) * $perPage;
        $set        = $query->skip($skip)->take($perPage)->get();

        // stupid paginator!
        $items = [];
        /** @var $item \TransactionJournal */
        foreach ($set as $item) {
            $items[] = $item;
        }
        $paginator = \Paginator::make($items, $totalItems, $perPage);
        $result    = [0 => [
            'date'      => 'Not in an envelope',
            'limit'     => null,
            'paginated' => true,
            'journals'  => $paginator
        ]];

        return $result;
    }
}