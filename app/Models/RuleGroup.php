<?php
/**
 * RuleGroup.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RuleGroup
 *
 * @package FireflyIII\Models
 * @property integer                                                                 $id
 * @property \Carbon\Carbon                                                          $created_at
 * @property \Carbon\Carbon                                                          $updated_at
 * @property string                                                                  $deleted_at
 * @property integer                                                                 $user_id
 * @property integer                                                                 $order
 * @property string                                                                  $title
 * @property string                                                                  $description
 * @property boolean                                                                 $active
 * @property-read \FireflyIII\User                                                   $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Rule[] $rules
 */
class RuleGroup extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rules()
    {
        return $this->hasMany('FireflyIII\Models\Rule');
    }

    /**
     * @param RuleGroup $value
     *
     * @return Rule
     */
    public static function routeBinder(RuleGroup $value)
    {
        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }
}
