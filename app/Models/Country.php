<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'provider_class',
    ];

    protected $appends = [
        'flag_url',
    ];

    public function currencies(): HasMany
    {
        return $this->hasMany(TransactionCurrency::class, 'country_id');
    }

    public function userGroups(): HasMany
    {
        return $this->hasMany(UserGroup::class, 'country_id');
    }

    public function getFlagUrlAttribute(): string
    {
        return sprintf('https://flagsapi.com/%s/flat/24.png', strtoupper($this->code));
    }

    /**
     * True when this country has a registered national-bank provider.
     */
    public function hasProvider(): bool
    {
        $class = (string) $this->provider_class;

        return '' !== $class && class_exists($class);
    }

    /**
     * Restrict the query to countries that have a working provider class.
     */
    public function scopeWithProvider(Builder $query): Builder
    {
        return $query->whereNotNull('provider_class');
    }
}
