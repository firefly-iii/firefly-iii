<?php

use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserTrait;
use LaravelBook\Ardent\Ardent;


/**
 * User
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $email
 * @property string $password
 * @property string $reset
 * @property string $remember_token
 * @property boolean $migrated
 * @property-read \Illuminate\Database\Eloquent\Collection|\Account[] $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\Component[] $components
 * @property-read \Illuminate\Database\Eloquent\Collection|\Preference[] $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|\RecurringTransaction[] $recurringtransactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @method static \Illuminate\Database\Query\Builder|\User whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereEmail($value) 
 * @method static \Illuminate\Database\Query\Builder|\User wherePassword($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereReset($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereRememberToken($value) 
 * @method static \Illuminate\Database\Query\Builder|\User whereMigrated($value) 
 */
class User extends Ardent implements UserInterface, RemindableInterface
{

    use UserTrait, RemindableTrait;


    public static $rules
        = [
            'email'    => 'required|email|unique:users,email',
            'migrated' => 'required|boolean',
            'password' => 'required|between:60,60',
            'reset'    => 'between:32,32',
        ];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['remember_token'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets()
    {
        return $this->hasMany('Budget');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reminders()
    {
        return $this->hasMany('Reminder');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybankreminders()
    {
        return $this->hasMany('PiggybankReminder');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany('Category');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function components()
    {
        return $this->hasMany('Component');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preferences()
    {
        return $this->hasMany('Preference');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recurringtransactions()
    {
        return $this->hasMany('RecurringTransaction');
    }

    /**
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function piggybanks()
    {
        return $this->hasManyThrough('Piggybank', 'Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }

}