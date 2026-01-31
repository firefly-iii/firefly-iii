<?php
/*
 * TriggersCreditRecalculation.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\Account;

use FireflyIII\Events\Model\Account\CreatedNewAccount;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use Illuminate\Support\Facades\Log;

class TriggersCreditRecalculation
{
    public function handle(CreatedNewAccount $event): void
    {
        L¬og::debug('Will call CreditRecalculateService because a new account was created.');
        $account = $event->account;
        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }


}
