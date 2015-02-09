<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Class Account
 *
 * @package FireflyIII\Models
 */
class Account extends Model
{
    use SoftDeletes, ValidatingTrait;

    protected $rules
        = [
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'name'            => 'required|between:1,100|uniqueForUser:accounts,name',
            'active'          => 'required|boolean'
        ];

    protected $fillable = ['user_id','account_type_id','name','active'];

    public function accountMeta()
    {
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    public function accountType()
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public function scopeAccountTypeIn(EloquentBuilder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
