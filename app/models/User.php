<?php

use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserTrait;
use LaravelBook\Ardent\Ardent;


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

}