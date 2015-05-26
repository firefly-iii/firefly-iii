<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AccountType
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $type
 * @property boolean $editable
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[] $accounts
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereEditable($value)
 */
class AccountType extends Model
{

    //
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }
}
