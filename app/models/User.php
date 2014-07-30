<?php

use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserTrait;
use LaravelBook\Ardent\Ardent;


/**
 * User
 *
 * @property integer                                                             $id
 * @property \Carbon\Carbon                                                      $created_at
 * @property \Carbon\Carbon                                                      $updated_at
 * @property string                                                              $email
 * @property string                                                              $password
 * @property string                                                              $reset
 * @property string                                                              $remember_token
 * @property boolean                                                             $migrated
 * @property-read \Illuminate\Database\Eloquent\Collection|\Account[]            $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\Preference[]         $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|\Component[]          $components
 * @property-read \Illuminate\Database\Eloquent\Collection|\Budget[]             $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\Category[]           $categories
 * @method static \Illuminate\Database\Query\Builder|\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereReset($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\User whereMigrated($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 */
class User extends Ardent implements UserInterface, RemindableInterface
{

    use UserTrait, RemindableTrait;


    public static $rules
        = [
            'email'    => 'required|email|unique:users,email',
            'migrated' => 'required|numeric|between:0,1',
            'password' => 'required|between:60,60',
            'reset'    => 'between:32,32',
        ];

    public static $factory
        = [
            'email'    => 'email',
            'password' => 'string|60',
            'migrated' => '0'

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
    protected $hidden = array('remember_token');

    public function accounts()
    {
        return $this->hasMany('Account');
    }

    public function preferences()
    {
        return $this->hasMany('Preference');
    }

    public function components()
    {
        return $this->hasMany('Component');
    }

    public function budgets()
    {
        return $this->hasMany('Budget');
    }

    public function categories()
    {
        return $this->hasMany('Category');
    }

    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }

}