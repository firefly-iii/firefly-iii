<?php

/**
 * PiggyBankEventFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Factory;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Create piggy bank events.
 *
 * Class PiggyBankEventFactory
 */
class PiggyBankEventFactory
{
    public function create(TransactionJournal $journal, ?PiggyBank $piggyBank): void
    {
        app('log')->debug(sprintf('Now in PiggyBankEventCreate for a %s', $journal->transactionType->type));
        if (null === $piggyBank) {
            app('log')->debug('Piggy bank is null');

            return;
        }

        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos = app(PiggyBankRepositoryInterface::class);
        $piggyRepos->setUser($journal->user);

        $amount     = $piggyRepos->getExactAmount($piggyBank, $journal);
        if (0 === bccomp($amount, '0')) {
            app('log')->debug('Amount is zero, will not create event.');

            return;
        }
        // amount can be negative here
        $piggyRepos->addAmountToPiggyBank($piggyBank, $amount, $journal);
    }
}
