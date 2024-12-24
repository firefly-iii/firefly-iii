<?php

/**
 * Account.php
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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperAccount
 */
class Account extends Model
{
    use Cachable;
    use HasFactory;
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
                                     = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'user_id'         => 'integer',
            'deleted_at'      => 'datetime',
            'active'          => 'boolean',
            'encrypted'       => 'boolean',
            'virtual_balance' => 'string',
        ];

    protected $fillable              = ['user_id', 'user_group_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban','native_virtual_balance'];

    protected $hidden                = ['encrypted'];
    private bool $joinedAccountTypes = false;

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $accountId = (int) $value;

            /** @var User $user */
            $user      = auth()->user();

            /** @var null|Account $account */
            $account   = $user->accounts()->with(['accountType'])->find($accountId);
            if (null !== $account) {
                return $account;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the account number.
     */
    public function getAccountNumberAttribute(): string
    {
        /** @var null|AccountMeta $metaValue */
        $metaValue = $this->accountMeta()
            ->where('name', 'account_number')
            ->first()
        ;

        return null !== $metaValue ? $metaValue->data : '';
    }

    public function accountMeta(): HasMany
    {
        return $this->hasMany(AccountMeta::class);
    }

    public function getEditNameAttribute(): string
    {
        $name = $this->name;

        if (AccountType::CASH === $this->accountType->type) {
            return '';
        }

        return $name;
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Get all the notes.
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

    public function piggyBanks(): BelongsToMany
    {
        return $this->belongsToMany(PiggyBank::class);
    }

    public function scopeAccountTypeIn(EloquentBuilder $query, array $types): void
    {
        if (false === $this->joinedAccountTypes) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    public function setVirtualBalanceAttribute(mixed $value): void
    {
        $value                               = (string) $value;
        if ('' === $value) {
            $value = null;
        }
        $this->attributes['virtual_balance'] = $value;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    /**
     * Get the user ID
     */
    protected function accountTypeId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    protected function iban(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => null === $value ? null : trim(str_replace(' ', '', (string) $value)),
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    /**
     * Get the virtual balance
     */
    protected function virtualBalance(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string) $value,
        );
    }
}
