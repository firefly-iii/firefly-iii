<?php


class Component extends Elegant
{
    public static $rules
        = [
            'user_id'           => 'exists:users,id|required',
            'name'              => 'required|between:1,255',
            'component_type_id' => 'required|exists:component_types,id'
        ];


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