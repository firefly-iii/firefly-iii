<?php
/**
 * Budget.php
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

use Eloquent;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Budget
 *
 * @property int                                  $id
 * @property Carbon|null      $created_at
 * @property Carbon|null      $updated_at
 * @property Carbon|null      $deleted_at
 * @property int                                  $user_id
 * @property string                               $name
 * @property bool                                 $active
 * @property bool                                 $encrypted
 * @property int                                  $order
 * @property-read Collection|Attachment[]         $attachments
 * @property-read int|null                        $attachments_count
 * @property-read Collection|AutoBudget[]         $autoBudgets
 * @property-read int|null                        $auto_budgets_count
 * @property-read Collection|BudgetLimit[]        $budgetlimits
 * @property-read int|null                        $budgetlimits_count
 * @property-read Collection|TransactionJournal[] $transactionJournals
 * @property-read int|null                        $transaction_journals_count
 * @property-read Collection|Transaction[]        $transactions
 * @property-read int|null                        $transactions_count
 * @property-read User                            $user
 * @method static \Illuminate\Database\Eloquent\Builder|Budget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Budget newQuery()
 * @method static Builder|Budget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Budget query()
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereUserId($value)
 * @method static Builder|Budget withTrashed()
 * @method static Builder|Budget withoutTrashed()
 * @mixin Eloquent
 */
class Budget extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'name', 'active', 'order'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Budget
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Budget
    {
        if (auth()->check()) {
            $budgetId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Budget $budget */
            $budget = $user->budgets()->find($budgetId);
            if (null !== $budget) {
                return $budget;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function autoBudgets(): HasMany
    {
        return $this->hasMany(AutoBudget::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function budgetlimits(): HasMany
    {
        return $this->hasMany(BudgetLimit::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class, 'budget_transaction_journal', 'budget_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'budget_transaction', 'budget_id');
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
