<?php
/**
 * AvailableBudget.php
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AvailableBudget.
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User $user
 * @property TransactionCurrency $transactionCurrency
 * @property int $transaction_currency_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $amount
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AvailableBudget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AvailableBudget whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AvailableBudget withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AvailableBudget withoutTrashed()
 * @mixin \Eloquent
 */
class AvailableBudget extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'              => 'datetime',
            'updated_at'              => 'datetime',
            'deleted_at'              => 'datetime',
            'start_date'              => 'date',
            'end_date'                => 'date',
            'transaction_currency_id' => 'int',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'transaction_currency_id', 'amount', 'start_date', 'end_date'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return AvailableBudget
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): AvailableBudget
    {
        if (auth()->check()) {
            $availableBudgetId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var AvailableBudget $availableBudget */
            $availableBudget = $user->availableBudgets()->find($availableBudgetId);
            if (null !== $availableBudget) {
                return $availableBudget;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
