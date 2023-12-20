<?php

/**
 * Rule.php
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
 * FireflyIII\Models\Rule
 *
 * @property int                      $id
 * @property null|Carbon              $created_at
 * @property null|Carbon              $updated_at
 * @property null|Carbon              $deleted_at
 * @property int                      $user_id
 * @property int                      $rule_group_id
 * @property string                   $title
 * @property null|string              $description
 * @property int                      $order
 * @property bool                     $active
 * @property bool                     $stop_processing
 * @property bool                     $strict
 * @property string                   $action_value
 * @property Collection|RuleAction[]  $ruleActions
 * @property null|int                 $rule_actions_count
 * @property RuleGroup                $ruleGroup
 * @property Collection|RuleTrigger[] $ruleTriggers
 * @property null|int                 $rule_triggers_count
 * @property User                     $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newQuery()
 * @method static Builder|Rule                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereRuleGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereStopProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereStrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereUserId($value)
 * @method static Builder|Rule                               withTrashed()
 * @method static Builder|Rule                               withoutTrashed()
 *
 * @property int $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereUserGroupId($value)
 *
 * @property null|UserGroup $userGroup
 *
 * @mixin Eloquent
 */
class Rule extends Model
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
            'order'           => 'int',
            'stop_processing' => 'boolean',
            'id'              => 'int',
            'strict'          => 'boolean',
        ];

    protected $fillable = ['rule_group_id', 'order', 'active', 'title', 'description', 'user_id', 'strict'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $ruleId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|Rule $rule */
            $rule = $user->rules()->find($ruleId);
            if (null !== $rule) {
                return $rule;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ruleActions(): HasMany
    {
        return $this->hasMany(RuleAction::class);
    }

    public function ruleGroup(): BelongsTo
    {
        return $this->belongsTo(RuleGroup::class);
    }

    public function ruleTriggers(): HasMany
    {
        return $this->hasMany(RuleTrigger::class);
    }

    /**
     * @param mixed $value
     */
    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = e($value);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function ruleGroupId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
