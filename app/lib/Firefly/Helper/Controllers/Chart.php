<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29-7-14
 * Time: 10:41
 */

namespace Firefly\Helper\Controllers;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;

class Chart implements ChartInterface
{

    public function account(\Account $account, Carbon $start, Carbon $end)
    {
        $current = clone $start;
        $today = new Carbon;
        $return = ['name' => $account->name, 'id' => $account->id, 'data' => []];

        while ($current <= $end) {
            \Log::debug('Now at day: '  . $current . '('.$current->timestamp.'), ('.($current->timestamp * 1000).') ');
            if ($current > $today) {
                $return['data'][] = [$current->timestamp * 1000, $account->predict(clone $current)];
            } else {
                $return['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
            }

            $current->addDay();
        }

        return $return;
    }

    public function accountDailySummary(\Account $account, Carbon $date)
    {
        $result = [
            'rows' => [],
            'sum' => 0
        ];
        if ($account) {
            // get journals in range:
            $journals = \Auth::user()->transactionjournals()->with(
                [
                    'transactions',
                    'transactions.account',
                    'transactioncurrency',
                    'transactiontype'
                ]
            )
                ->distinct()
                ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->where('transactions.account_id', $account->id)
                ->where('transaction_journals.date', $date->format('Y-m-d'))
                ->orderBy('transaction_journals.date', 'DESC')
                ->orderBy('transaction_journals.id', 'DESC')
                ->get(['transaction_journals.*']);

            // loop all journals:
            foreach ($journals as $journal) {
                foreach ($journal->transactions as $transaction) {
                    $name = $transaction->account->name;
                    if ($transaction->account->id != $account->id) {
                        $result['rows'][$name] = isset($result[$name]) ? $result[$name] + floatval($transaction->amount)
                            : floatval($transaction->amount);
                        $result['sum'] += floatval($transaction->amount);
                    }
                }
            }
        }

        return $result;

    }

    /**
     * @return array
     */
    public function budgets(Carbon $start)
    {
        // grab all budgets in the time period, like the index does:
        // get the budgets for this period:

        $data = [];

        $budgets = \Auth::user()->budgets()->with(
            ['limits' => function ($q) {
                    $q->orderBy('limits.startdate', 'ASC');
                }, 'limits.limitrepetitions' => function ($q) use ($start) {
                    $q->orderBy('limit_repetitions.startdate', 'ASC');
                    $q->where('startdate', $start->format('Y-m-d'));
                }]
        )->orderBy('name', 'ASC')->get();

        foreach ($budgets as $budget) {
            $budget->count = 0;
            foreach ($budget->limits as $limit) {
                /** @var $rep \LimitRepetition */
                foreach ($limit->limitrepetitions as $rep) {
                    $rep->left = $rep->left();
                    // overspent:
                    if ($rep->left < 0) {
                        $rep->spent = ($rep->left * -1) + $rep->amount;
                        $rep->overspent = $rep->left * -1;
                        $total = $rep->spent + $rep->overspent;
                        $rep->spent_pct = round(($rep->spent / $total) * 100);
                        $rep->overspent_pct = 100 - $rep->spent_pct;
                    } else {
                        $rep->spent = $rep->amount - $rep->left;
                        $rep->spent_pct = round(($rep->spent / $rep->amount) * 100);
                        $rep->left_pct = 100 - $rep->spent_pct;


                    }
                }
                $budget->count += count($limit->limitrepetitions);
            }
        }

        $limitInPeriod = 'Envelope for XXX';
        $spentInPeriod = 'Spent in XXX';

        $data['series'] = [
            [
                'name' => $limitInPeriod,
                'data' => []
            ],
            [
                'name' => $spentInPeriod,
                'data' => []
            ],
        ];


        foreach ($budgets as $budget) {
            if ($budget->count > 0) {
                $data['labels'][] = wordwrap($budget->name, 12, "<br>");
            }
            foreach ($budget->limits as $limit) {
                foreach ($limit->limitrepetitions as $rep) {
                    //0: envelope for period:
                    $amount = floatval($rep->amount);
                    $spent = $rep->spent;
                    $color = $spent > $amount ? '#FF0000' : null;
                    $data['series'][0]['data'][] = $amount;
                    $data['series'][1]['data'][] = ['y' => $rep->spent, 'color' => $color];
                }
            }


        }

        return $data;
    }

    public function categoryShowChart(\Category $category, $range, Carbon $start, Carbon $end)
    {
        $data = ['name' => $category->name . ' per ' . $range, 'data' => []];
        // go back twelve periods. Skip if empty.
        $beginning = clone $start;
        switch ($range) {
            default:
                throw new FireflyException('No beginning for range ' . $range);
                break;
            case '1D':
                $beginning->subDays(12);
                break;
            case '1W':
                $beginning->subWeeks(12);
                break;
            case '1M':
                $beginning->subYear();
                break;
            case '3M':
                $beginning->subYears(3);
                break;
            case '6M':
                $beginning->subYears(6);
                break;
            case 'custom':
                $diff = $start->diff($end);
                $days = $diff->days;
                $beginning->subDays(12 * $days);
                break;
        }

        // loop over the periods:
        while ($beginning <= $start) {
            // increment currentEnd to fit beginning:
            $currentEnd = clone $beginning;
            // increase beginning for next round:
            switch ($range) {
                default:
                    throw new FireflyException('No currentEnd incremental for range ' . $range);
                    break;
                case '1D':
                    break;
                case '1W':
                    $currentEnd->addWeek()->subDay();
                    break;
                case '1M':
                    $currentEnd->addMonth()->subDay();
                    break;
                case '3M':
                    $currentEnd->addMonths(3)->subDay();
                    break;
                case '6M':
                    $currentEnd->addMonths(6)->subDay();
                    break;
                case 'custom':
                    $diff = $start->diff($end);
                    $days = $diff->days;
                    $days = $days == 1 ? 2 : $days;
                    $currentEnd->addDays($days)->subDay();
                    break;
            }



            // now format the current range:
            $title = '';
            switch ($range) {
                default:
                    throw new \Firefly\Exception\FireflyException('No date formats for frequency "' . $range . '"!');
                    break;
                case '1D':
                    $title = $beginning->format('j F Y');
                    break;
                case '1W':
                    $title = $beginning->format('\W\e\e\k W, Y');
                    break;
                case '1M':
                    $title = $beginning->format('F Y');
                    break;
                case '3M':
                case '6M':
                    $title = $beginning->format('M Y') . ' - ' . $currentEnd->format('M Y');
                    break;
                case 'custom':
                    $title = $beginning->format('d-m-Y').' - '.$currentEnd->format('d-m-Y');
                    break;
                case 'yearly':
//                    return $this->startdate->format('Y');
                    break;
            }


            // get sum for current range:
            $journals = \TransactionJournal::
                with(
                    ['transactions' => function ($q) {
                            $q->where('amount', '>', 0);
                        }]
                )
                ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                ->where('transaction_types.type', 'Withdrawal')
                ->leftJoin('component_transaction_journal', 'component_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('components', 'components.id', '=', 'component_transaction_journal.component_id')
                ->where('components.id', '=', $category->id)
                //->leftJoin()
                ->after($beginning)->before($currentEnd)
                ->where('completed', 1)
                ->get(['transaction_journals.*']);
            $currentSum = 0;
            foreach ($journals as $journal) {
                if (!isset($journal->transactions[0])) {
                    throw new FireflyException('Journal #' . $journal->id . ' has ' . count($journal->transactions)
                        . ' transactions!');
                }
                $transaction = $journal->transactions[0];
                $amount = floatval($transaction->amount);
                $currentSum += $amount;

            }
            $data['data'][] = [$title, $currentSum];

            // increase beginning for next round:
            switch ($range) {
                default:
                    throw new FireflyException('No incremental for range ' . $range);
                    break;
                case '1D':
                    $beginning->addDay();
                    break;
                case '1W':
                    $beginning->addWeek();
                    break;
                case '1M':
                    $beginning->addMonth();
                    break;
                case '3M':
                    $beginning->addMonths(3);
                    break;
                case '6M':
                    $beginning->addMonths(6);
                    break;
                case 'custom':
                    $diff = $start->diff($end);
                    $days = $diff->days;

                    $beginning->addDays($days);
                    break;

            }
        }
        return $data;


    }

    public function categories(Carbon $start, Carbon $end)
    {

        $result = [];
        // grab all transaction journals in this period:
        $journals = \TransactionJournal::
            with(
                ['components', 'transactions' => function ($q) {
                        $q->where('amount', '>', 0);
                    }]
            )
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', 'Withdrawal')
            ->after($start)->before($end)
            ->where('completed', 1)
            ->get(['transaction_journals.*']);
        foreach ($journals as $journal) {
            // has to be one:

            if (!isset($journal->transactions[0])) {
                throw new FireflyException('Journal #' . $journal->id . ' has ' . count($journal->transactions)
                    . ' transactions!');
            }
            $transaction = $journal->transactions[0];
            $amount = floatval($transaction->amount);

            // get budget from journal:
            $category = $journal->categories()->first();
            $categoryName = is_null($category) ? '(no category)' : $category->name;

            $result[$categoryName] = isset($result[$categoryName]) ? $result[$categoryName] + floatval($amount)
                : $amount;

        }
        unset($journal, $transaction, $category, $amount);

        // sort
        arsort($result);
        $chartData = [];
        foreach ($result as $name => $value) {
            $chartData[] = [$name, $value];
        }


        return $chartData;
    }

}