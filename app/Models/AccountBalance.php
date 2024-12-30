<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Casts\SeparateTimezoneCaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAccountBalance
 */
class AccountBalance extends Model
{
    use HasFactory;

    protected $fillable = ['account_id', 'title', 'transaction_currency_id', 'balance', 'date', 'date_tz'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    protected function casts(): array
    {
        return [
            'date'    => SeparateTimezoneCaster::class,
            'balance' => 'string',
        ];
    }
}
