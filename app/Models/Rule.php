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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Rule.
 *
 * @property bool       $stop_processing
 * @property int        $id
 * @property Collection $ruleTriggers
 * @property Collection $ruleActions
 * @property bool       $active
 * @property bool       $strict
 * @property User       $user
 * @property Carbon     $created_at
 * @property Carbon     $updated_at
 * @property string     $title
 * @property int        $order
 * @property RuleGroup  $ruleGroup
 * @property int        $rule_group_id
 * @property string     $description
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereRuleGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereStopProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereStrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Rule whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule withoutTrashed()
 * @mixin \Eloquent
 */
class Rule extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
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
    /** @var array Fields that can be filled */
    protected $fillable = ['rule_group_id', 'order', 'active', 'title', 'description', 'user_id', 'strict'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Rule
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Rule
    {
        if (auth()->check()) {
            $ruleId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Rule $rule */
            $rule = $user->rules()->find($ruleId);
            if (null !== $rule) {
                return $rule;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function ruleActions(): HasMany
    {
        return $this->hasMany(RuleAction::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function ruleGroup(): BelongsTo
    {
        return $this->belongsTo(RuleGroup::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function ruleTriggers(): HasMany
    {
        return $this->hasMany(RuleTrigger::class);
    }

    /**
     * @param $value
     * @codeCoverageIgnore
     */
    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = e($value);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
