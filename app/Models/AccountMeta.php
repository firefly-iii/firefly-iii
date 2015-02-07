<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class AccountMeta extends Model
{

    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }


    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

}
