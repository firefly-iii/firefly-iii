<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\CurrencyExchangeRate;

use Carbon\Carbon;
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
    public function handle(CreatedCurrencyExchangeRate|DestroyedCurrencyExchangeRate|UpdatedCurrencyExchangeRate $event): void
    {
        Preferences::mark();
        Cache::clear();
        if ($event instanceof DestroyedCurrencyExchangeRate) {
            $this->handleCurrency($event->userGroup, $event->from, $event->date);
            $this->handleCurrency($event->userGroup, $event->to, $event->date);

            return;
        }
        $this->handleCurrency($event->rate->userGroup, $event->rate->fromCurrency, $event->rate->date);
        $this->handleCurrency($event->rate->userGroup, $event->rate->toCurrency, $event->rate->date);
    }

    private function handleCurrency(UserGroup $userGroup, TransactionCurrency $currency, Carbon $date): void
    {
        $calculator = new PrimaryAmountRecalculationService();
        $calculator->setDate($date);
        if (Amount::convertToPrimary()) {
            $date->startOfDay();
            Log::debug(sprintf('Will now convert amounts to primary currency for currency %s after %s.', $currency->code, $date->format('Y-m-d')));

            $calculator->recalculateForGroupAndCurrency($userGroup, $currency);
            //            $calculator->recalculateForGroup($userGroup);

            return;
        }
        Log::debug('Will NOT convert to primary currency.');
    }
}
