<?php
/**
 * RuleTrigger.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RuleTrigger.
 *
 * @property string $trigger_value
 * @property string $trigger_type
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int    $order
 * @property bool   $active
 * @property bool   $stop_processing
 * @property int $rule_id
 * @property-read \FireflyIII\Models\Rule $rule
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereStopProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereTriggerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereTriggerValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RuleTrigger whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RuleTrigger extends Model
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
    protected $fillable = ['rule_id', 'trigger_type', 'trigger_value', 'order', 'active', 'stop_processing'];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }
}
