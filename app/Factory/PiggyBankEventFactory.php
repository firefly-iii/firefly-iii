<?php
/**
 * PiggyBankEventFactory.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;

/**
 * Create piggy bank events.
 *
 * Class PiggyBankEventFactory
 */
class PiggyBankEventFactory
{
    /**
     * @param TransactionJournal $journal
     * @param PiggyBank|null     $piggyBank
     *
     * @return PiggyBankEvent|null
     */
    public function create(TransactionJournal $journal, ?PiggyBank $piggyBank): ?PiggyBankEvent
    {
        Log::debug(sprintf('Now in PiggyBankEventCreate for a %s', $journal->transactionType->type));
        if (is_null($piggyBank)) {
            return null;
        }

        // is a transfer?
        if (!(TransactionType::TRANSFER === $journal->transactionType->type)) {
            Log::info(sprintf('Will not connect %s #%d to a piggy bank.', $journal->transactionType->type, $journal->id));

            return null;
        }

        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos = app(PiggyBankRepositoryInterface::class);
        $piggyRepos->setUser($journal->user);

        // repetition exists?
        $repetition = $piggyRepos->getRepetition($piggyBank, $journal->date);
        if (null === $repetition->id) {
            Log::error(sprintf('No piggy bank repetition on %s!', $journal->date->format('Y-m-d')));

            return null;
        }

        // get the amount
        $amount = $piggyRepos->getExactAmount($piggyBank, $repetition, $journal);
        if (0 === bccomp($amount, '0')) {
            Log::debug('Amount is zero, will not create event.');

            return null;
        }

        // update amount
        $piggyRepos->addAmountToRepetition($repetition, $amount);
        $event = $piggyRepos->createEventWithJournal($piggyBank, $amount, $journal);

        Log::debug(sprintf('Created piggy bank event #%d', $event->id));

        return $event;
    }
}