<?php
/**
 * Rule.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Rule
 *
 * @package FireflyIII\Models
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property integer $user_id
 * @property integer $rule_group_id
 * @property integer $order
 * @property string $title
 * @property string $description
 * @property boolean $active
 * @property boolean $stop_processing
 * @property-read \FireflyIII\User $user
 * @property-read \FireflyIII\Models\RuleGroup $ruleGroup
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleAction[] $ruleActions
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleTrigger[] $ruleTriggers
 */
class Rule extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruleGroup()
    {
        return $this->belongsTo('FireflyIII\Models\RuleGroup');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ruleActions()
    {
        return $this->hasMany('FireflyIII\Models\RuleAction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ruleTriggers()
    {
        return $this->hasMany('FireflyIII\Models\RuleTrigger');
    }


}
