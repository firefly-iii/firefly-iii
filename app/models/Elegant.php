<?php

use \Illuminate\Database\Eloquent\Model as Eloquent;
class Elegant extends Eloquent
{
    public static $rules = [];
    public $validator;

    public function isValid()
    {
        $validator = Validator::make(
            $this->toArray(),
            $this::$rules
        );
        $this->validator = $validator;
        return $validator->passes();
    }
} 