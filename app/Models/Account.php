<?php namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Account
 *
 * @property integer                                                                        $id
 * @property \Carbon\Carbon                                                                 $created_at
 * @property \Carbon\Carbon                                                                 $updated_at
 * @property \Carbon\Carbon                                                                 $deleted_at
 * @property integer                                                                        $user_id
 * @property integer                                                                        $account_type_id
 * @property string                                                                         $name
 * @property boolean                                                                        $active
 * @property boolean                                                                        $encrypted
 * @property float                                                                          $virtual_balance
 * @property string                                                                         $iban
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\AccountMeta[] $accountMeta
 * @property-read \FireflyIII\Models\AccountType                                            $accountType
 * @property-read mixed                                                                     $name_for_editform
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBank[]   $piggyBanks
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions
 * @property-read \FireflyIII\User                                                          $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account accountTypeIn($types)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account hasMetaValue($name, $value)
 * @property string                                                                         $startBalance
 * @property string                                                                         $endBalance
 * @property float                                                                          $difference
 * @property \Carbon\Carbon                                                                 $lastActivityDate
 * @property float                                                                          $piggyBalance
 * @property float                                                                          $percentage
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereAccountTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereEncrypted($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereVirtualBalance($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account whereIban($value)
 * @mixin \Eloquent
 */
class Account extends Model
{
    use SoftDeletes, ValidatingTrait;

    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    /** @var array */
    protected $fillable = ['user_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban'];
    /** @var array */
    protected $hidden = ['virtual_balance_encrypted', 'encrypted'];
    protected $rules
                      = [
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'name'            => 'required',
            'active'          => 'required|boolean',
        ];
    /** @var  bool */
    private $joinedAccountTypes;

    /**
     * @param array $fields
     *
     * @return Account|null
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query  = Account::orderBy('id');
        $search = $fields;
        unset($search['name'], $search['iban']);

        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($set as $account) {
            if ($account->name == $fields['name']) {
                return $account;
            }
        }
        // account must have a name. If not set, use IBAN.
        if (!isset($fields['name'])) {
            $fields['name'] = $fields['iban'];
        }

        // create it!
        $account = Account::create($fields);

        return $account;

    }

    /**
     * @param array $fields
     *
     * @return Account|null
     */
    public static function firstOrNullEncrypted(array $fields)
    {
        // everything but the name:
        $query  = Account::orderBy('id');
        $search = $fields;
        unset($search['name']);
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($set as $account) {
            if ($account->name == $fields['name']) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @param Account $value
     *
     * @return Account
     */
    public static function routeBinder(Account $value)
    {

        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountMeta()
    {
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountType()
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getIbanAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getMeta($fieldName): string
    {
        foreach ($this->accountMeta as $meta) {
            if ($meta->name == $fieldName) {
                return $meta->data;
            }
        }

        return '';
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     *
     * @return string
     */
    public function getNameForEditformAttribute()
    {
        $name = $this->name;
        if ($this->accountType->type == 'Cash account') {
            $name = '';
        }

        return $name;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBanks()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBank');
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param string          $name
     * @param string          $value
     */
    public function scopeHasMetaValue(EloquentBuilder $query, $name, $value)
    {
        $joinName = str_replace('.', '_', $name);
        $query->leftJoin(
            'account_meta as ' . $joinName, function (JoinClause $join) use ($joinName, $name) {
            $join->on($joinName . '.account_id', '=', 'accounts.id')->where($joinName . '.name', '=', $name);
        }
        );
        $query->where($joinName . '.data', json_encode($value));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setIbanAttribute($value)
    {
        $this->attributes['iban'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setVirtualBalanceAttribute($value)
    {
        $this->attributes['virtual_balance'] = strval(round($value, 2));
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
