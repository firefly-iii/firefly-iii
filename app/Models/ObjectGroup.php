<?php
declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class ObjectGroup
 */
class ObjectGroup extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function piggyBanks()
    {
        return $this->morphedByMany(PiggyBank::class, 'object_groupable');
    }
}
