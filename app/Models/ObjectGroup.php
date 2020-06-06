<?php
declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class ObjectGroup
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBank[] $piggyBanks
 * @property-read int|null $piggy_banks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup query()
 * @mixin \Eloquent
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
