<?php

/**
 * Bill.php
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Bill
 *
 * @property int                             $id
 * @property null|Carbon                     $created_at
 * @property null|Carbon                     $updated_at
 * @property null|Carbon                     $deleted_at
 * @property int                             $user_id
 * @property int                             $transaction_currency_id
 * @property string                          $name
 * @property string                          $match
 * @property string                          $amount_min
 * @property string                          $amount_max
 * @property Carbon                          $date
 * @property null|Carbon                     $end_date
 * @property null|Carbon                     $extension_date
 * @property string                          $repeat_freq
 * @property int                             $skip
 * @property bool                            $automatch
 * @property bool                            $active
 * @property bool                            $name_encrypted
 * @property bool                            $match_encrypted
 * @property int                             $order
 * @property Attachment[]|Collection         $attachments
 * @property null|int                        $attachments_count
 * @property Collection|Note[]               $notes
 * @property null|int                        $notes_count
 * @property Collection|ObjectGroup[]        $objectGroups
 * @property null|int                        $object_groups_count
 * @property null|TransactionCurrency        $transactionCurrency
 * @property Collection|TransactionJournal[] $transactionJournals
 * @property null|int                        $transaction_journals_count
 * @property User                            $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newQuery()
 * @method static Builder|Bill                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill query()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAutomatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereExtensionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatchEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereNameEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereRepeatFreq($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereSkip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUserId($value)
 * @method static Builder|Bill                               withTrashed()
 * @method static Builder|Bill                               withoutTrashed()
 *
 * @property int $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUserGroupId($value)
 *
 * @mixin Eloquent
 */
class Bill extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'date'            => 'date',
            'end_date'        => 'date',
            'extension_date'  => 'date',
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
        ];

    protected $fillable
        = [
            'name',
            'match',
            'amount_min',
            'user_id',
            'user_group_id',
            'amount_max',
            'date',
            'repeat_freq',
            'skip',
            'automatch',
            'active',
            'transaction_currency_id',
            'end_date',
            'extension_date',
        ];

    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $billId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|Bill $bill */
            $bill = $user->bills()->find($billId);
            if (null !== $bill) {
                return $bill;
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

    /**
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get all the tags for the post.
     */
    public function objectGroups(): MorphToMany
    {
        return $this->morphToMany(ObjectGroup::class, 'object_groupable');
    }

    /**
     * @param mixed $value
     */
    public function setAmountMaxAttribute($value): void
    {
        $this->attributes['amount_max'] = (string)$value;
    }

    /**
     * @param mixed $value
     */
    public function setAmountMinAttribute($value): void
    {
        $this->attributes['amount_min'] = (string)$value;
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * Get the max amount
     */
    protected function amountMax(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    /**
     * Get the min amount
     */
    protected function amountMin(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    /**
     * Get the skip
     */
    protected function skip(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function transactionCurrencyId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
