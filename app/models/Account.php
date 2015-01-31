<?php

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Illuminate\Database\Query\JoinClause;
use Watson\Validating\ValidatingTrait;

/**
 * Class Account
 */
class Account extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;
    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules
                            = [
            'name'            => ['required', 'between:1,100'],
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'active'          => 'required|boolean'

        ];
    protected     $dates    = ['deleted_at', 'created_at', 'updated_at'];
    protected     $fillable = ['name', 'user_id', 'account_type_id', 'active'];

    /**
     * Account type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountType()
    {
        return $this->belongsTo('AccountType');
    }

    /**
     *
     * @param $fieldName
     *
     * @return mixed
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBanks()
    {
        return $this->hasMany('PiggyBank');
    }

    /**
     *
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
     *
     * @param EloquentBuilder $query
     */
    public function scopeWithMeta(EloquentBuilder $query, $field = 'accountRole')
    {
        $query->leftJoin(
            'account_meta', function (JoinClause $join) use ($field) {
            $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', $field);
        }
        );
    }

    /**
     * Transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

    /**
     * @param $fieldName
     * @param $fieldValue
     *
     * @return AccountMeta
     */
    public function updateMeta($fieldName, $fieldValue)
    {
        $meta = $this->accountMeta()->get();
        /** @var AccountMeta $entry */
        foreach ($meta as $entry) {
            if ($entry->name == $fieldName) {
                $entry->data = $fieldValue;
                $entry->save();

                return $entry;
            }
        }
        $meta = new AccountMeta;
        $meta->account()->associate($this);
        $meta->name = $fieldName;
        $meta->data = $fieldValue;
        $meta->save();

        return $meta;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountMeta()
    {
        return $this->hasMany('AccountMeta');
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }


}
