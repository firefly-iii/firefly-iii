<?php
/**
 * Rule.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
 * @property string     $text
 * @property int        $order
 * @property RuleGroup  $ruleGroup
 * @property int        $rule_group_id
 * @property string     $description
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
