<?php
/**
 * RuleTrigger.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\RuleTrigger
 *
 * @property integer                      $id
 * @property \Carbon\Carbon               $created_at
 * @property \Carbon\Carbon               $updated_at
 * @property integer                      $rule_id
 * @property integer                      $order
 * @property string                       $title
 * @property string                       $trigger_type
 * @property string                       $trigger_value
 * @property boolean                      $active
 * @property boolean                      $stop_processing
 * @property-read \FireflyIII\Models\Rule $rule
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereRuleId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereStopProcessing($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereTriggerType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleTrigger whereTriggerValue($value)
 * @mixin \Eloquent
 */
class RuleTrigger extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rule()
    {
        return $this->belongsTo('FireflyIII\Models\Rule');
    }
}
