<?php
/**
 * RuleAction.php
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FireflyIII\Models\RuleAction
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $rule_id
 * @property string $action_type
 * @property string $action_value
 * @property int $order
 * @property bool $active
 * @property bool $stop_processing
 * @property-read \FireflyIII\Models\Rule $rule
 * @method static Builder|RuleAction newModelQuery()
 * @method static Builder|RuleAction newQuery()
 * @method static Builder|RuleAction query()
 * @method static Builder|RuleAction whereActionType($value)
 * @method static Builder|RuleAction whereActionValue($value)
 * @method static Builder|RuleAction whereActive($value)
 * @method static Builder|RuleAction whereCreatedAt($value)
 * @method static Builder|RuleAction whereId($value)
 * @method static Builder|RuleAction whereOrder($value)
 * @method static Builder|RuleAction whereRuleId($value)
 * @method static Builder|RuleAction whereStopProcessing($value)
 * @method static Builder|RuleAction whereUpdatedAt($value)
 * @mixin Eloquent
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
