<?php


class AccountType extends Eloquent
{

    public static $factory
        = [
            'description' => 'string'
        ];

    public function accounts()
    {
        return $this->hasMany('Account');
    }
} 