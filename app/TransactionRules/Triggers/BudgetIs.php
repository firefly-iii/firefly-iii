<?php
/**
 * BudgetIs.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Triggers;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class BudgetIs
 *
 * @package FireflyIII\TransactionRules\Triggers
 */
final class BudgetIs extends AbstractTrigger implements TriggerInterface
{

    /**
     * A trigger is said to "match anything", or match any given transaction,
     * when the trigger value is very vague or has no restrictions. Easy examples
     * are the "AmountMore"-trigger combined with an amount of 0: any given transaction
     * has an amount of more than zero! Other examples are all the "Description"-triggers
     * which have hard time handling empty trigger values such as "" or "*" (wild cards).
     *
     * If the user tries to create such a trigger, this method MUST return true so Firefly III
     * can stop the storing / updating the trigger. If the trigger is in any way restrictive
     * (even if it will still include 99.9% of the users transactions), this method MUST return
     * false.
     *
     * @param null $value
     *
     * @return bool
     */
    public static function willMatchEverything($value = null)
    {
        if (!is_null($value)) {
            return false;
        }
        Log::error(sprintf('Cannot use %s with a null value.', self::class));

        return true;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function triggered(TransactionJournal $journal): bool
    {
        $budget = $journal->budgets()->first();
        if (!is_null($budget)) {
            $name = strtolower($budget->name);
            // match on journal:
            if ($name === strtolower($this->triggerValue)) {
                Log::debug(sprintf('RuleTrigger BudgetIs for journal #%d: "%s" is "%s", return true.', $journal->id, $name, $this->triggerValue));

                return true;
            }
        }

        if (is_null($budget)) {
            // perhaps transactions have this budget?
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $budget = $transaction->budgets()->first();
                if (!is_null($budget)) {
                    $name = strtolower($budget->name);
                    if ($name === strtolower($this->triggerValue)) {
                        Log::debug(
                            sprintf(
                                'RuleTrigger BudgetIs for journal #%d (transaction #%d): "%s" is "%s", return true.',
                                $journal->id, $transaction->id, $name, $this->triggerValue
                            )
                        );

                        return true;
                    }
                }
            }
        }

        Log::debug(sprintf('RuleTrigger BudgetIs for journal #%d: does not have budget "%s", return false.', $journal->id, $this->triggerValue));

        return false;
    }
}
