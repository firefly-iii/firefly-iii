<?php
/**
 * RuleAction.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
/**
 * RuleAction.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * FireflyIII\Models\RuleAction
 *
 * @property integer                      $id
 * @property \Carbon\Carbon               $created_at
 * @property \Carbon\Carbon               $updated_at
 * @property integer                      $rule_id
 * @property integer                      $order
 * @property boolean                      $active
 * @property boolean                      $stop_processing
 * @property string                       $action_type
 * @property string                       $action_value
 * @property-read \FireflyIII\Models\Rule $rule
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereRuleId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereStopProcessing($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereActionType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RuleAction whereActionValue($value)
 * @mixin \Eloquent
 */
class RuleAction extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rule()
    {
        return $this->belongsTo('FireflyIII\Models\Rule');
    }
}
