<?php
declare(strict_types = 1);

namespace FireflyIII;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
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
 * @property string                                                                                $remember_token
 * @property string                                                                                $reset
 * @property boolean                                                                               $blocked
 * @property string                                                                                $blocked_code
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[]            $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[]         $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[]                $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Bill[]               $bills
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]             $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]           $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Preference[]         $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionjournals
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Role[]               $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleGroup[]          $ruleGroups
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Rule[]               $rules
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\ExportJob[] $exportjobs
 */
class User extends Authenticatable
{
    use EntrustUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'password', 'blocked', 'blocked_code'];


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
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    /**
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Attachment');
    }

    /**
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Bill');
    }

    /**
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Budget');
    }

    /**
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Category');
    }

    /**
     * @return HasMany
     */
    public function exportjobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ExportJob');
    }

    /**
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\PiggyBank', 'FireflyIII\Models\Account');
    }

    /**
     * @return HasMany
     */
    public function preferences(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Preference');
    }

    /**
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\RuleGroup');
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Rule');
    }

    /**
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Tag');
    }

    /**
     * @return HasMany
     */
    public function transactionjournals(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return HasManyThrough
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\Transaction', 'FireflyIII\Models\TransactionJournal');
    }

}
