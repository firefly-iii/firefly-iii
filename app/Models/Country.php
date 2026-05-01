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

    protected $appends = [
        'flag_url',
    ];

    public function currencies(): HasMany
    {
        return $this->hasMany(TransactionCurrency::class, 'country_id');
    }

    public function getFlagUrlAttribute(): string
    {
        return sprintf('https://flagsapi.com/%s/flat/24.png', strtoupper($this->code));
    }
}
