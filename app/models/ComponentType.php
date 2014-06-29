<?php


class ComponentType extends Eloquent
{
    public function components()
    {
        return $this->hasMany('Component');
    }

} 