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
            'email'        => 'email|unique:users,email',
            'migrated'     => 'numeric|between:0,1',
            'password'     => 'between:60,60',
            'verification' => 'between:32,32',
        ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('password', 'remember_token');

    public function accounts() {
        return $this->hasMany('Account');
    }

}
