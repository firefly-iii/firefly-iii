<?php

namespace FireflyIII\Support\Twig;

use Auth;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Support\CacheProperties;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * @codeCoverageIgnore
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
        $functions   = [];
        $functions[] = new Twig_SimpleFunction(
            'spentInRepetition', function (LimitRepetition $repetition) {
            $cache = new CacheProperties;
            $cache->addProperty($repetition->id);
            $cache->addProperty('spentInRepetition');
            if ($cache->has()) {
                return $cache->get(); // @codeCoverageIgnore
            }
            $sum
                = Auth::user()->transactionjournals()
                      ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                      ->leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budget_transaction_journal.budget_id')
                      ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                      ->before($repetition->enddate)
                      ->after($repetition->startdate)
                      ->where('limit_repetitions.id', '=', $repetition->id)
                      ->get(['transaction_journals.*'])->sum('amount');
            $cache->store($sum);

            return $sum;
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
