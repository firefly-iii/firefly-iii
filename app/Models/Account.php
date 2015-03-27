<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;
use Crypt;
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
            'name'            => 'required|between:1,1024|uniqueAccountForUser',
            'active'          => 'required|boolean'
        ];

    protected $fillable = ['user_id', 'account_type_id', 'name', 'active'];

    /**
     * @param $fieldName
     *
     * @return string|null
     */
    public function getMeta($fieldName)
    {
        foreach ($this->accountMeta as $meta) {
            if ($meta->name == $fieldName) {
                return $meta->data;
            }
        }

        return null;

    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        // @codeCoverageIgnoreStart
        return $value;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountMeta()
    {
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountType()
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeAccountTypeIn(EloquentBuilder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBanks()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBank');
    }

}
