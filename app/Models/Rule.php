<?php
/**
 * Rule.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Rule
 *
 * @package FireflyIII\Models
 * @property integer                                                                        $id
 * @property \Carbon\Carbon                                                                 $created_at
 * @property \Carbon\Carbon                                                                 $updated_at
 * @property string                                                                         $deleted_at
 * @property integer                                                                        $user_id
 * @property integer                                                                        $rule_group_id
 * @property integer                                                                        $order
 * @property string                                                                         $title
 * @property string                                                                         $description
 * @property boolean                                                                        $active
 * @property boolean                                                                        $stop_processing
 * @property-read \FireflyIII\User                                                          $user
 * @property-read \FireflyIII\Models\RuleGroup                                              $ruleGroup
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleAction[]  $ruleActions
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleTrigger[] $ruleTriggers
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereRuleGroupId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereStopProcessing($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Rule whereDescription($value)
 * @mixin \Eloquent
 */
class Rule extends Model
{
    use SoftDeletes;

    /**
     * @param Rule $value
     *
     * @return Rule
     */
    public static function routeBinder(Rule $value)
    {
        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ruleActions()
    {
        return $this->hasMany('FireflyIII\Models\RuleAction');
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
    public function ruleTriggers()
    {
        return $this->hasMany('FireflyIII\Models\RuleTrigger');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
