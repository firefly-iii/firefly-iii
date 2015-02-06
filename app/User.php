<?php namespace FireflyIII;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{

    use Authenticatable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];
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

    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    public function bills()
    {
        return $this->hasMany('FireflyIII\Models\Bill');
    }

    public function budgets()
    {
        return $this->hasMany('FireflyIII\Models\Budget');
    }

    public function categories()
    {
        return $this->hasMany('FireflyIII\Models\Category');
    }

    public function piggyBanks()
    {
        return $this->hasManyThrough('FireflyIII\Models\PiggyBank', 'Account');
    }

    public function preferences()
    {
        return $this->hasMany('FireflyIII\Models\Preference');
    }

    public function reminders()
    {
        return $this->hasMany('FireflyIII\Models\Reminder');
    }

    public function transactionjournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = \Hash::make($value);
    }

}
