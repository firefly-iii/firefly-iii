<?php
/**
 * AvailableBudget.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\AvailableBudget
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @property int $transaction_currency_id
 * @property string $amount
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property-read \FireflyIII\Models\TransactionCurrency $transactionCurrency
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget newQuery()
 * @method static Builder|AvailableBudget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AvailableBudget whereUserId($value)
 * @method static Builder|AvailableBudget withTrashed()
 * @method static Builder|AvailableBudget withoutTrashed()
 * @mixin Eloquent
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
     * @throws NotFoundHttpException
     * @return AvailableBudget
     */
    public static function routeBinder(string $value): AvailableBudget
    {
        if (auth()->check()) {
            $availableBudgetId = (int) $value;
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
