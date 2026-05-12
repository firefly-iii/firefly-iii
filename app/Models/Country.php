<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function currencies(): HasMany
    {
        return $this->hasMany(TransactionCurrency::class, 'country_id');
    }

}
