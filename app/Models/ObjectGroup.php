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

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\ObjectGroup
 *
 * @property int                    $id
 * @property int                    $user_id
 * @property null|Carbon            $created_at
 * @property null|Carbon            $updated_at
 * @property null|Carbon            $deleted_at
 * @property string                 $title
 * @property int                    $order
 * @property Account[]|Collection   $accounts
 * @property null|int               $accounts_count
 * @property Bill[]|Collection      $bills
 * @property null|int               $bills_count
 * @property Collection|PiggyBank[] $piggyBanks
 * @property null|int               $piggy_banks_count
 * @property User                   $user
 *
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
 *
 * @property int $user_group_id
 *
 * @method static Builder|ObjectGroup whereUserGroupId($value)
 *
 * @mixin Eloquent
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
            $objectGroup = self::where('object_groups.id', $objectGroupId)
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
