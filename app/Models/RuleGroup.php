<?php

/**
 * RuleGroup.php
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\RuleGroup
 *
 * @property int               $id
 * @property null|Carbon       $created_at
 * @property null|Carbon       $updated_at
 * @property null|Carbon       $deleted_at
 * @property int               $user_id
 * @property null|string       $title
 * @property null|string       $description
 * @property int               $order
 * @property bool              $active
 * @property bool              $stop_processing
 * @property Collection|Rule[] $rules
 * @property null|int          $rules_count
 * @property User              $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup newQuery()
 * @method static Builder|RuleGroup                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereStopProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereUserId($value)
 * @method static Builder|RuleGroup                               withTrashed()
 * @method static Builder|RuleGroup                               withoutTrashed()
 *
 * @property int $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RuleGroup whereUserGroupId($value)
 *
 * @mixin Eloquent
 */
class RuleGroup extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'active'          => 'boolean',
            'stop_processing' => 'boolean',
            'order'           => 'int',
        ];

    protected $fillable = ['user_id', 'user_group_id', 'stop_processing', 'order', 'title', 'description', 'active'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $ruleGroupId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|RuleGroup $ruleGroup */
            $ruleGroup = $user->ruleGroups()->find($ruleGroupId);
            if (null !== $ruleGroup) {
                return $ruleGroup;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
