<?php

namespace FireflyIII\Support\Twig;

use Auth;
use DB;
use FireflyIII\Models\LimitRepetition;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class Budget
 *
 * @package FireflyIII\Support\Twig
 */
class Budget extends Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        $functions[] = new Twig_SimpleFunction(
            'spentInRepetition', function (LimitRepetition $repetition) {
            $sum = DB::table('transactions')
                     ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                     ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budget_transaction_journal.budget_id')
                     ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                     ->where('transaction_journals.date', '>=', $repetition->startdate->format('Y-m-d'))
                     ->where('transaction_journals.date', '<=', $repetition->enddate->format('Y-m-d'))
                     ->where('transaction_journals.user_id', Auth::user()->id)
                     ->whereNull('transactions.deleted_at')
                     ->where('transactions.amount', '>', 0)
                     ->where('limit_repetitions.id', '=', $repetition->id)
                     ->sum('transactions.amount');

            return floatval($sum);
        }
        );

        return $functions;

    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\Budget';
    }
}