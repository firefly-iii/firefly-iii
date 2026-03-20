<?php
/*
 * ProcessesExchangeRates.php
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

namespace FireflyIII\Listeners\Model\CurrencyExchangeRate;

use FireflyIII\Events\Model\CurrencyExchangeRate\CreatedCurrencyExchangeRate;
use FireflyIII\Events\Model\CurrencyExchangeRate\DestroyedCurrencyExchangeRate;
use FireflyIII\Events\Model\CurrencyExchangeRate\UpdatedCurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Services\Internal\Recalculate\PrimaryAmountRecalculationService;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessesExchangeRates
{
    public function handle(CreatedCurrencyExchangeRate | UpdatedCurrencyExchangeRate | DestroyedCurrencyExchangeRate $event): void
    {
        Preferences::mark();
        Cache::clear();
        if ($event instanceof DestroyedCurrencyExchangeRate) {
            $this->handleCurrency($event->userGroup, $event->from);
            $this->handleCurrency($event->userGroup, $event->to);
            return;
        }
        $this->handleCurrency($event->rate->userGroup, $event->rate->fromCurrency);
        $this->handleCurrency($event->rate->userGroup, $event->rate->toCurrency);
    }

    private function handleCurrency(UserGroup $userGroup, TransactionCurrency $currency): void
    {

        $calculator = new PrimaryAmountRecalculationService();
        if (Amount::convertToPrimary()) {
            Log::debug(sprintf('Will now convert amounts to primary currency for currency %s.', $currency->code));

            $calculator->recalculateForGroupAndCurrency($userGroup, $currency);
//            $calculator->recalculateForGroup($userGroup);

            return;
        }
        Log::debug('Will NOT convert to primary currency.');

    }

}
