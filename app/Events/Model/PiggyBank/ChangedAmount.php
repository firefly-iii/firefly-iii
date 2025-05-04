<?php

/*
 * ChangedPiggyBankAmount.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Events\Model\PiggyBank;

use FireflyIII\Events\Event;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class ChangedAmount
 */
class ChangedAmount extends Event
{
    use SerializesModels;

    public string    $amount;
    public PiggyBank $piggyBank;

    /**
     * Create a new event instance.
     */
    public function __construct(PiggyBank $piggyBank, string $amount, public ?TransactionJournal $transactionJournal, public ?TransactionGroup $transactionGroup)
    {
        app('log')->debug(sprintf('Created piggy bank event for piggy bank #%d with amount %s', $piggyBank->id, $amount));
        $this->piggyBank = $piggyBank;
        $this->amount    = $amount;
    }
}
