<?php


class AccountType extends Eloquent {

    public function accounts() {
        return $this->hasMany('Account');
    }

} 