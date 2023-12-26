<?php

/**
 * PiggyBank.php
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
 * FireflyIII\Models\PiggyBank
 *
 * @property int                              $id
 * @property null|Carbon                      $created_at
 * @property null|Carbon                      $updated_at
 * @property null|Carbon                      $deleted_at
 * @property int                              $account_id
 * @property string                           $name
 * @property string                           $targetamount
 * @property null|Carbon                      $startdate
 * @property null|Carbon                      $targetdate
 * @property int                              $order
 * @property bool                             $active
 * @property bool                             $encrypted
 * @property Account                          $account
 * @property Attachment[]|Collection          $attachments
 * @property null|int                         $attachments_count
 * @property Collection|Note[]                $notes
 * @property null|int                         $notes_count
 * @property Collection|ObjectGroup[]         $objectGroups
 * @property null|int                         $object_groups_count
 * @property Collection|PiggyBankEvent[]      $piggyBankEvents
 * @property null|int                         $piggy_bank_events_count
 * @property Collection|PiggyBankRepetition[] $piggyBankRepetitions
 * @property null|int                         $piggy_bank_repetitions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank newQuery()
 * @method static Builder|PiggyBank                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank query()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereStartdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereTargetamount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereTargetdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereUpdatedAt($value)
 * @method static Builder|PiggyBank                               withTrashed()
 * @method static Builder|PiggyBank                               withoutTrashed()
 *
 * @mixin Eloquent
 */
class PiggyBank extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'startdate'  => 'date',
            'targetdate' => 'date',
            'order'      => 'int',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];

    protected $fillable = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate', 'active'];

    protected $hidden = ['targetamount_encrypted', 'encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $piggyBankId = (int)$value;
            $piggyBank   = self::where('piggy_banks.id', $piggyBankId)
                ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                ->where('accounts.user_id', auth()->user()->id)->first(['piggy_banks.*'])
            ;
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        throw new NotFoundHttpException();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all the piggy bank's notes.
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

    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany(PiggyBankEvent::class);
    }

    public function piggyBankRepetitions(): HasMany
    {
        return $this->hasMany(PiggyBankRepetition::class);
    }

    /**
     * @param mixed $value
     */
    public function setTargetamountAttribute($value): void
    {
        $this->attributes['targetamount'] = (string)$value;
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    /**
     * Get the max amount
     */
    protected function targetamount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }
}
