<?php namespace FireflyIII\Models;

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
        return $this->hasMany('Account');
    }

    public function bills()
    {
        return $this->hasMany('Bill');
    }

    public function budgets()
    {
        return $this->hasMany('Budget');
    }

    public function categories()
    {
        return $this->hasMany('Category');
    }

    public function piggyBanks()
    {
        return $this->hasManyThrough('PiggyBank', 'Account');
    }

    public function preferences()
    {
        return $this->hasMany('Preference');
    }

    public function reminders()
    {
        return $this->hasMany('Reminder');
    }

    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

}
