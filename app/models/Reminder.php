<?php

use LaravelBook\Ardent\Ardent;


/**
 * Reminder
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $user_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $enddate
 * @property boolean $active
 * @property string $title
 * @property string $data
 * @property-read \User $user
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereEnddate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereActive($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereTitle($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereData($value) 
 */
class Reminder extends Ardent
{

    protected $table = 'reminders';


    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

    /**
     * Expected data in this value:
     *
     * type: Piggybank, Test
     * action_uri: where to go when the user wants to do this?
     * text: full text to present to user
     * amount: any relevant amount.
     *
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }


} 