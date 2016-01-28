<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\AccountType
 *
 * @property integer                                                 $id
 * @property \Carbon\Carbon                                          $created_at
 * @property \Carbon\Carbon                                          $updated_at
 * @property string                                                  $type
 * @property boolean                                                 $editable
 * @property-read \Illuminate\Database\Eloquent\Collection|Account[] $accounts
 */
class AccountType extends Model
{

    protected $dates = ['created_at', 'updated_at'];

    //
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }
}
