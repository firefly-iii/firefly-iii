<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
use Watson\Validating\ValidatingTrait;

class AccountType extends Eloquent
{
    use ValidatingTrait;
    public static $rules
        = [
            'type'     => ['required', 'between:1,50', 'alphabasic'],
            'editable' => 'required|boolean',

        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('Account');
    }
} 