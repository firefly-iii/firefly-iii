<?php namespace FireflyIII;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Zizaco\Entrust\Traits\EntrustUserTrait;

/**
 * Class User
 *
 * @package FireflyIII
 * @property integer                                                                               $id
 * @property \Carbon\Carbon                                                                        $created_at
 * @property \Carbon\Carbon                                                                        $updated_at
 * @property string                                                                                $email
 * @property string                                                                                $password
 * @property string                                                                                $reset
 * @property string                                                                                $remember_token
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[]            $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[]                $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Bill[]               $bills
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]             $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]           $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Preference[]         $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionjournals
 * @property-read \Illuminate\Database\Eloquent\Collection|\Config::get('entrust.role[] $roles
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereReset($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereRememberToken($value)
 * @property boolean $blocked
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[] $attachments
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereBlocked($value)
 * @property string $blocked_code 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereBlockedCode($value)
 */
class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{

    use Authenticatable, CanResetPassword, EntrustUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'password','blocked','blocked_code'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany('FireflyIII\Models\Attachment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany('FireflyIII\Models\Tag');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bills()
    {
        return $this->hasMany('FireflyIII\Models\Bill');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets()
    {
        return $this->hasMany('FireflyIII\Models\Budget');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany('FireflyIII\Models\Category');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function piggyBanks()
    {
        return $this->hasManyThrough('FireflyIII\Models\PiggyBank', 'FireflyIII\Models\Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function transactions()
    {
        return $this->hasManyThrough('FireflyIII\Models\Transaction', 'FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preferences()
    {
        return $this->hasMany('FireflyIII\Models\Preference');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

}
