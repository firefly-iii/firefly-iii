<?php
declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class ObjectGroup
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBank[] $piggyBanks
 * @property-read int|null                                                                $piggy_banks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup query()
 * @mixin \Eloquent
 * @property int                                                                          $id
 * @property \Illuminate\Support\Carbon|null                                              $created_at
 * @property \Illuminate\Support\Carbon|null                                              $updated_at
 * @property string|null                                                                  $deleted_at
 * @property string                                                                       $title
 * @property int                                                                          $order
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ObjectGroup whereUpdatedAt($value)
 */
class ObjectGroup extends Model
{
    protected $fillable = ['title', 'order'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function piggyBanks()
    {
        return $this->morphedByMany(PiggyBank::class, 'object_groupable');
    }
}
