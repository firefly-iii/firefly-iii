<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\CurrencyExchangeRate;

use FireflyIII\Events\Event;
use FireflyIII\Models\CurrencyExchangeRate;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreatedCurrencyExchangeRate extends Event
{
    use SerializesModels;

    public function __construct(
        public CurrencyExchangeRate $rate
    ) {
        Log::debug(sprintf('CreatedCurrencyExchangeRate(#%d) Event', $rate->id));
    }
}
