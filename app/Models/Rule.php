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
