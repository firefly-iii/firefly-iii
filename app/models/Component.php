<?php


class Component extends Eloquent
{
    public function componentType()
    {
        return $this->belongsTo('ComponentType');
    }

    public function transactions()
    {
        return $this->belongsToMany('Transaction');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }

} 