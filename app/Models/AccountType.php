<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FireflyIII\Models\AccountType
 *
 * @property integer                                                 $id
 * @property \Carbon\Carbon                                          $created_at
 * @property \Carbon\Carbon                                          $updated_at
 * @property string                                                  $type
 * @property boolean                                                 $editable
 * @property-read \Illuminate\Database\Eloquent\Collection|Account[] $accounts
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereEditable($value)
 * @mixin \Eloquent
 */
class AccountType extends Model
{
    const DEFAULT         = 'Default account';
    const CASH            = 'Cash account';
    const ASSET           = 'Asset account';
    const EXPENSE         = 'Expense account';
    const REVENUE         = 'Revenue account';
    const INITIAL_BALANCE = 'Initial balance account';
    const BENEFICIARY     = 'Beneficiary account';
    const IMPORT          = 'Import account';


    protected $dates = ['created_at', 'updated_at'];

    //
    /**
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }
}
