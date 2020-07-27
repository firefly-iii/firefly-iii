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


use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ObjectGroup
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBank[] $piggyBanks
 * @property-read int|null                                                                $piggy_banks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup query()
 * @mixin \Eloquent
 * @property int                                                                          $id
 * @property \Illuminate\Support\Carbon|null                                              $created_at
 * @property \Illuminate\Support\Carbon|null                                              $updated_at
 * @property string|null                                                                  $deleted_at
 * @property string                                                                       $title
 * @property int                                                                          $order
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereUpdatedAt($value)
 */
class ObjectGroup extends Model
{
    protected $fillable = ['title', 'order', 'user_id'];

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function piggyBanks()
    {
        return $this->morphedByMany(PiggyBank::class, 'object_groupable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function bills()
    {
        return $this->morphedByMany(Bill::class, 'object_groupable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function accounts()
    {
        return $this->morphedByMany(Account::class, 'object_groupable');
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @return ObjectGroup
     */
    public static function routeBinder(string $value): ObjectGroup
    {
        if (auth()->check()) {
            $objectGroupId = (int) $value;
            /** @var ObjectGroup $objectGroup */
            $objectGroup   = self::where('object_groups.id', $objectGroupId)
                                 ->where('object_groups.user_id', auth()->user()->id)->first();
            if (null !== $objectGroup) {
                return $objectGroup;
            }
        }
        throw new NotFoundHttpException;
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
