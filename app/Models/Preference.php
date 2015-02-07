<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{

    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

}
