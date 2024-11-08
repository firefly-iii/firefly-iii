<?php
/*
 * PiggyBankObserver.php
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

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;

/**
 * Class PiggyBankObserver
 */
class PiggyBankObserver
{
    public function created(PiggyBank $piggyBank): void
    {
        app('log')->debug('Observe "created" of a piggy bank.');
        $repetition                = new PiggyBankRepetition();
        $repetition->piggyBank()->associate($piggyBank);
        $repetition->startdate     = $piggyBank->startdate;
        $repetition->startdate_tz  = $piggyBank->startdate->format('e');
        $repetition->targetdate    = $piggyBank->targetdate;
        $repetition->targetdate_tz = $piggyBank->targetdate?->format('e');
        $repetition->currentamount = '0';
        $repetition->save();
    }

    /**
     * Also delete related objects.
     */
    public function deleting(PiggyBank $piggyBank): void
    {
        app('log')->debug('Observe "deleting" of a piggy bank.');

        foreach ($piggyBank->attachments()->get() as $attachment) {
            $attachment->delete();
        }

        $piggyBank->piggyBankEvents()->delete();
        $piggyBank->piggyBankRepetitions()->delete();

        $piggyBank->notes()->delete();
    }
}
