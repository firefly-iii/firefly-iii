<?php

/**
 * ObjectGroup.php
 * Copyright (c) 2020 james@firefly-iii.org
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\ObjectGroup
 *
 * @property int                         $id
 * @property int                         $user_id
 * @property Carbon|null                 $created_at
 * @property Carbon|null                 $updated_at
 * @property Carbon|null                 $deleted_at
 * @property string                      $title
 * @property int                         $order
 * @property-read Collection|Account[]   $accounts
 * @property-read int|null               $accounts_count
 * @property-read Collection|Bill[]      $bills
 * @property-read int|null               $bills_count
 * @property-read Collection|PiggyBank[] $piggyBanks
 * @property-read int|null               $piggy_banks_count
 * @property-read User                   $user
 * @method static Builder|ObjectGroup newModelQuery()
 * @method static Builder|ObjectGroup newQuery()
 * @method static Builder|ObjectGroup query()
 * @method static Builder|ObjectGroup whereCreatedAt($value)
 * @method static Builder|ObjectGroup whereDeletedAt($value)
 * @method static Builder|ObjectGroup whereId($value)
 * @method static Builder|ObjectGroup whereOrder($value)
 * @method static Builder|ObjectGroup whereTitle($value)
 * @method static Builder|ObjectGroup whereUpdatedAt($value)
 * @method static Builder|ObjectGroup whereUserId($value)
 * @mixin Eloquent
 */
class ObjectGroup extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_id'    => 'integer',
            'deleted_at' => 'datetime',
        ];
    protected $fillable = ['title', 'order', 'user_id'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return ObjectGroup
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): ObjectGroup
    {
        if (auth()->check()) {
            $objectGroupId = (int) $value;
            /** @var ObjectGroup $objectGroup */
            $objectGroup = self::where('object_groups.id', $objectGroupId)
                               ->where('object_groups.user_id', auth()->user()->id)->first();
            if (null !== $objectGroup) {
                return $objectGroup;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return MorphToMany
     */
    public function accounts()
    {
        return $this->morphedByMany(Account::class, 'object_groupable');
    }

    /**
     * @return MorphToMany
     */
    public function bills()
    {
        return $this->morphedByMany(Bill::class, 'object_groupable');
    }

    /**
     * @return MorphToMany
     */
    public function piggyBanks()
    {
        return $this->morphedByMany(PiggyBank::class, 'object_groupable');
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
