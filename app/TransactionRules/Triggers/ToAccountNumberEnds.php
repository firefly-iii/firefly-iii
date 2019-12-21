<?php
/**
 * ToAccountNumberEnds.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Log;

/**
 * Class ToAccountNumberEnds.
 */
final class ToAccountNumberEnds extends AbstractTrigger implements TriggerInterface
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
     * @param mixed $value
     *
     * @return bool
     */
    public static function willMatchEverything($value = null): bool
    {
        if (null !== $value) {
            $res = '' === (string)$value;
            if (true === $res) {
                Log::error(sprintf('Cannot use %s with "" as a value.', self::class));
            }

            return $res;
        }
        Log::error(sprintf('Cannot use %s with a null value.', self::class));

        return true;
    }

    /**
     * Returns true when from account ends with X
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function triggered(TransactionJournal $journal): bool
    {
        /** @var JournalRepositoryInterface $repository */
        $repository   = app(JournalRepositoryInterface::class);
        $dest       = $repository->getDestinationAccount($journal);
        $search       = strtolower($this->triggerValue);
        $searchLength = strlen($search);

        $part1 = substr($dest->iban, $searchLength * -1);
        $part2 = substr($dest->account_number, $searchLength * -1);

        if (strtolower($part1) === $search
            || strtolower($part2) === $search) {
            Log::debug(
                sprintf(
                    'RuleTrigger %s for journal #%d: "%s" or "%s" ends with "%s", return true.',
                    get_class($this), $journal->id, $part1, $part2, $search
                )
            );

            return true;
        }

        Log::debug(
            sprintf(
                'RuleTrigger %s for journal #%d: "%s" and "%s" do not end with "%s", return false.',
                get_class($this), $journal->id, $part1, $part2, $search
            )
        );

        return false;
    }
}
