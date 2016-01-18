<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\AccountType
 *
 * @property integer                   $id
 * @property Carbon                    $created_at
 * @property Carbon                    $updated_at
 * @property string                    $type
 * @property boolean                   $editable
 * @property-read Collection|Account[] $accounts
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
