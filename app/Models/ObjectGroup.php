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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperObjectGroup
 */
class ObjectGroup extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_id'    => 'integer',
            'deleted_at' => 'datetime',
        ];
    protected $fillable = ['title', 'order', 'user_id', 'user_group_id'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $objectGroupId = (int)$value;

            /** @var null|ObjectGroup $objectGroup */
            $objectGroup   = self::where('object_groups.id', $objectGroupId)
                ->where('object_groups.user_id', auth()->user()->id)->first()
            ;
            if (null !== $objectGroup) {
                return $objectGroup;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
