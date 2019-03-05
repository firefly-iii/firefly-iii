<?php
/**
 * RuleAction.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RuleAction.
 *
 * @property string $action_value
 * @property string $action_type
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int    $order
 * @property bool   $active
 * @property bool   $stop_processing
 * @property Rule   $rule
 * @property int $rule_id
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereActionValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereStopProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleAction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RuleAction extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'active'          => 'boolean',
            'order'           => 'int',
            'stop_processing' => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['rule_id', 'action_type', 'action_value', 'order', 'active', 'stop_processing'];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }
}
