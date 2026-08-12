<?php




declare(strict_types=1);



namespace FireflyIII\Handlers\ExchangeRate;

use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;

class ConversionParameters
{
    public User $user;
    public Model $model;
    public ?TransactionCurrency $originalCurrency = null;
    public string $amountField;
    public string $primaryAmountField;
    public Carbon $date;

    public function __construct()
    {
        $this->date = now();
    }
}
