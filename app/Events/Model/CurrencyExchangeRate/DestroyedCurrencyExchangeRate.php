<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\CurrencyExchangeRate;

use Carbon\Carbon;
use FireflyIII\Events\Event;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyedCurrencyExchangeRate extends Event
{
    use SerializesModels;

    public function __construct(
        public TransactionCurrency $from,
        public TransactionCurrency $to,
        public UserGroup $userGroup,
        public Carbon $date
    ) {
        Log::debug(sprintf('DestroyedCurrencyExchangeRate(%s, %s) Event', $from->code, $to->code));
    }
}
