<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\System;

use FireflyIII\Events\Preferences\UserGroupChangedPrimaryCurrency;
use FireflyIII\Services\Internal\Recalculate\PrimaryAmountRecalculationService;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Support\Facades\Log;

class RecalculatesPrimaryCurrencyAmounts
{
    public function handle(UserGroupChangedPrimaryCurrency $event): void
    {
        // fire laravel command to recalculate them all.
        if (Amount::convertToPrimary()) {
            Log::debug('Will now convert amounts to primary currency.');
            $calculator = new PrimaryAmountRecalculationService();
            $calculator->recalculate();

            return;
        }
        Log::debug('Will NOT convert to primary currency.');
    }
}
