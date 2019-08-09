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
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\BudgetLimit[] $budgetLimits
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionJournals
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereDecimalPlaces($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionCurrency whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency withoutTrashed()
 * @mixin \Eloquent
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
