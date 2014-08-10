<?php

use LaravelBook\Ardent\Ardent;


/**
 * Preference
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $user_id
 * @property string         $name
 * @property string         $data
 * @property-read \User     $user
 * @method static \Illuminate\Database\Query\Builder|\Preference whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Preference whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Preference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Preference whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Preference whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Preference whereData($value)
 */
class Preference extends Ardent
{
    public static $rules
        = [
            'user_id' => 'required|exists:users,id',
            'name'    => 'required|between:1,255',
            'data'    => 'required'
        ];

    public static $factory
        = [
            'user_id' => 'factory|User',
            'name'    => 'string',
            'data'    => 'string'
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

} 