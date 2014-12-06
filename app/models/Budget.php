<?php

class Budget extends Component
{
    protected $isSubclass = true;

    public function limitrepetitions()
    {
        return $this->hasManyThrough('LimitRepetition', 'Limit', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function limits()
    {
        return $this->hasMany('Limit', 'component_id');
    }


} 