<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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

    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    public function scopeAccountTypeIn(EloquentBuilder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

}
