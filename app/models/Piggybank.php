<?php
use LaravelBook\Ardent\Ardent as Ardent;

class Piggybank extends Ardent
{
    public static $rules
        = [
            'name'       => 'required|between:1,255',
            'account_id' => 'required|exists:accounts,id',
            'targetdate' => 'date',
            'amount'     => 'required|min:0',
            'target'     => 'required|min:1',
            'order'      => 'required:min:1',
        ];

    public function account()
    {
        return $this->belongsTo('Account');
    }

} 