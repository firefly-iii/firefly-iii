<?php

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    protected $fillable = ['account_id', 'title', 'transaction_currency_id', 'balance'];
    use HasFactory;


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }
}
