<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Elegant implements UserInterface, RemindableInterface
{

    use UserTrait, RemindableTrait;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    public static $rules
        = [
            'email'        => 'required|email|unique:users,email',
            'migrated'     => 'required|numeric|between:0,1',
            'password'     => 'required|between:60,60',
            'verification' => 'between:32,32',
        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('remember_token');

    public function accounts() {
        return $this->hasMany('Account');
    }

}