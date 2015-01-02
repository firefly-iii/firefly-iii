<?php
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class Component
 */
class Component extends Eloquent
{

    public static $rules
                                 = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,100|alphabasic',
            'class'   => 'required',
        ];
    protected     $dates         = ['deleted_at', 'created_at', 'updated_at'];
    protected     $fillable      = ['name', 'user_id','class'];
    protected     $table         = 'components';
    use ValidatingTrait;
} 
