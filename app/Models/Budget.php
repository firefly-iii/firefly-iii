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

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Budget
 *
 * @property int                             $id
 * @property null|Carbon                     $created_at
 * @property null|Carbon                     $updated_at
 * @property null|Carbon                     $deleted_at
 * @property int                             $user_id
 * @property string                          $name
 * @property bool                            $active
 * @property bool                            $encrypted
 * @property int                             $order
 * @property Attachment[]|Collection         $attachments
 * @property null|int                        $attachments_count
 * @property AutoBudget[]|Collection         $autoBudgets
 * @property null|int                        $auto_budgets_count
 * @property BudgetLimit[]|Collection        $budgetlimits
 * @property null|int                        $budgetlimits_count
 * @property Collection|TransactionJournal[] $transactionJournals
 * @property null|int                        $transaction_journals_count
 * @property Collection|Transaction[]        $transactions
 * @property null|int                        $transactions_count
 * @property User                            $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Budget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Budget newQuery()
 * @method static Builder|Budget                               onlyTrashed()
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
 * @method static Builder|Budget                               withTrashed()
 * @method static Builder|Budget                               withoutTrashed()
 *
 * @property string $email
 * @property int    $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Budget whereUserGroupId($value)
 *
 * @property Collection|Note[] $notes
 * @property null|int          $notes_count
 *
 * @mixin Eloquent
 */
class Budget extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];

    protected $fillable = ['user_id', 'name', 'active', 'order', 'user_group_id'];

    protected $hidden = ['encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $budgetId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|Budget $budget */
            $budget = $user->budgets()->find($budgetId);
            if (null !== $budget) {
                return $budget;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function autoBudgets(): HasMany
    {
        return $this->hasMany(AutoBudget::class);
    }

    public function budgetlimits(): HasMany
    {
        return $this->hasMany(BudgetLimit::class);
    }

    /**
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class, 'budget_transaction_journal', 'budget_id');
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'budget_transaction', 'budget_id');
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
