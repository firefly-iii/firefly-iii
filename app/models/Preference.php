<?php

use LaravelBook\Ardent\Ardent;


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

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    //
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

} 