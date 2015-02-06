<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Account
 *
 * @package FireflyIII\Models
 */
class Account extends Model
{

    public function accountMeta()
    {
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    public function accountType()
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


}
