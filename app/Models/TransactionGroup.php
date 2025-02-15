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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransactionGroup extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
                        = [
            'id'         => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'title'      => 'string',
            'date'       => 'datetime',
            'user_id'                => 'integer',
            'user_group_id'                => 'integer',
        ];

    protected $fillable = ['user_id', 'user_group_id', 'title'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        app('log')->debug(sprintf('Now in %s("%s")', __METHOD__, $value));
        if (auth()->check()) {
            $groupId = (int) $value;

            /** @var User $user */
            $user    = auth()->user();
            app('log')->debug(sprintf('User authenticated as %s', $user->email));

            /** @var null|TransactionGroup $group */
            $group   = $user->transactionGroups()
                ->with(['transactionJournals', 'transactionJournals.transactions'])
                ->where('transaction_groups.id', $groupId)->first(['transaction_groups.*'])
            ;
            if (null !== $group) {
                app('log')->debug(sprintf('Found group #%d.', $group->id));

                return $group;
            }
        }
        app('log')->debug('Found no group.');

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
}
