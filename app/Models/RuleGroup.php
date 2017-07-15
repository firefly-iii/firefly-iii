<?php
/**
 * RuleGroup.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RuleGroup
 *
 * @package FireflyIII\Models
 */
class RuleGroup extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
            'active'     => 'boolean',
            'order'      => 'int',
        ];
    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    protected $fillable = ['user_id', 'order', 'title', 'description', 'active'];

    /**
     * @param RuleGroup $value
     *
     * @return RuleGroup
     */
    public static function routeBinder(RuleGroup $value)
    {
        if (auth()->check()) {
            if (intval($value->user_id) === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rules()
    {
        return $this->hasMany('FireflyIII\Models\Rule');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
