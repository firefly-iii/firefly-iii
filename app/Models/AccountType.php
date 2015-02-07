<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{

    //
    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }
}
