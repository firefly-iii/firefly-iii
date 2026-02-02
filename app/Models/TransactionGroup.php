<?php

/**
 * TransactionGroup.php
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

use FireflyIII\Handlers\Observer\DeletedTransactionGroupObserver;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property User                           $user
 * @property UserGroup                      $userGroup
 * @property Collection<TransactionJournal> $transactionJournals
 */
#[ObservedBy([DeletedTransactionGroupObserver::class])]
class TransactionGroup extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $fillable = ['user_id', 'user_group_id', 'title'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(self|string $value): self
    {
        if ($value instanceof self) {
            $value = (int) $value->id;
        }
        Log::debug(sprintf('Now in %s("%s")', __METHOD__, $value));
        if (auth()->check()) {
            $groupId = (int) $value;

            /** @var User $user */
            $user    = auth()->user();
            Log::debug(sprintf('User authenticated as %s', $user->email));

            /** @var null|TransactionGroup $group */
            $group   = $user
                ->transactionGroups()
                ->with(['transactionJournals', 'transactionJournals.transactions'])
                ->where('transaction_groups.id', $groupId)
                ->first(['transaction_groups.*'])
            ;
            if (null !== $group) {
                Log::debug(sprintf('Found group #%d.', $group->id));

                return $group;
            }
        }
        Log::debug('Found no group.');

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'deleted_at'    => 'datetime',
            'title'         => 'string',
            'date'          => 'datetime',
            'user_id'       => 'integer',
            'user_group_id' => 'integer',
        ];
    }
}
