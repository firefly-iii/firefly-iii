<?php

/*
 * AccountObserver.php
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

use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;

/**
 * Class AccountObserver
 */
class AccountObserver
{
    /**
     * Also delete related objects.
     */
    public function deleting(Account $account): void
    {
        app('log')->debug('Observe "deleting" of an account.');
        $account->accountMeta()->delete();
        /** @var PiggyBank $piggy */
        foreach ($account->piggyBanks()->get() as $piggy) {
            $piggy->accounts()->detach($account);
        }
        foreach ($account->attachments()->get() as $attachment) {
            $attachment->delete();
        }
        foreach ($account->transactions()->get() as $transaction) {
            $transaction->delete();
        }
        $account->notes()->delete();
        $account->locations()->delete();
    }
}
