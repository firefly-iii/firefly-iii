<?php
/**
 * TransactionCurrency.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionCurrency.
 *
 * @property string $code
 * @property string $symbol
 * @property int    $decimal_places
 * @property int    $id
 * @property string name
 * @property bool   $enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 */
class TransactionCurrency extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
            'decimal_places' => 'int',
            'enabled'        => 'bool',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['name', 'code', 'symbol', 'decimal_places', 'enabled'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return TransactionCurrency
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): TransactionCurrency
    {
        if (auth()->check()) {
            $currencyId = (int)$value;
            $currency   = self::find($currencyId);
            if (null !== $currency) {
                return $currency;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function budgetLimits(): HasMany
    {
        return $this->hasMany(BudgetLimit::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
