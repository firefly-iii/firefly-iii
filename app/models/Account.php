<?php
use LaravelBook\Ardent\Ardent as Ardent;
use LaravelBook\Ardent\Builder;

/**
 * Account
 *
 * @property integer                                                      $id
 * @property \Carbon\Carbon                                               $created_at
 * @property \Carbon\Carbon                                               $updated_at
 * @property integer                                                      $user_id
 * @property integer                                                      $account_type_id
 * @property string                                                       $name
 * @property boolean                                                      $active
 * @property-read \AccountType                                            $accountType
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[] $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\Piggybank[]   $piggybanks
 * @property-read \User                                                   $user
 * @method static \Illuminate\Database\Query\Builder|\Account whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereAccountTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereActive($value)
 * @method static \Account accountTypeIn($types)
 * @property-read \Illuminate\Database\Eloquent\Collection|\AccountMeta[] $accountMeta
 * @method static \Account withMeta() 
 */
class Account extends Ardent
{

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

    /**
     * Fillable fields.
     *
     * @var array
     */
    protected $fillable = ['name', 'user_id', 'account_type_id', 'active'];

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
    public function piggybanks()
    {
        return $this->hasMany('Piggybank');
    }

    /**
     *
     * @param Builder $query
     * @param array   $types
     */
    public function scopeAccountTypeIn(Builder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     *
     * @param Builder $query
     */
    public function scopeWithMeta(Builder $query)
    {
        $query->with(['accountmeta']);
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
        $meta->name  = $fieldName;
        $meta->data = $fieldValue;
        $meta->save();

        return $meta;
    }

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