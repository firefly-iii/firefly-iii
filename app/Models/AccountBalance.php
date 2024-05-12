<?php

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    protected $fillable = ['account_id', 'transaction_currency_id', 'balance'];
    use HasFactory;
}
