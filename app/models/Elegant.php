<?php


class Elegant extends Eloquent
{
    public static $rules = [];

    public function isValid()
    {
        return Validator::make(
            $this->toArray(),
            $this::$rules
        )->passes();
    }
} 