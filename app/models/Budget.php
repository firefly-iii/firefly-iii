<?php

/**
 * Class Budget
 */
class Budget extends Component
{
    // @codingStandardsIgnoreStart
    protected $isSubclass = true;
    // @codingStandardsIgnoreEnd

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function limitrepetitions()
    {
        return $this->hasManyThrough('LimitRepetition', 'BudgetLimit', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetlimits()
    {
        return $this->hasMany('BudgetLimit', 'component_id');
    }


} 