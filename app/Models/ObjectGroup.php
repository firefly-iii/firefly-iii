<?php
declare(strict_types=1);

namespace FireflyIII\Models;


use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    protected $fillable = ['title', 'order', 'user_id'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_id'    => 'integer',
            'deleted_at' => 'datetime',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function piggyBanks()
    {
        return $this->morphedByMany(PiggyBank::class, 'object_groupable');
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @return ObjectGroup
     */
    public static function routeBinder(string $value): ObjectGroup
    {
        if (auth()->check()) {
            $objectGroupId = (int) $value;
            $objectGroup   = self::where('object_groups.id', $objectGroupId)
                                 ->where('object_groups.user_id', auth()->user()->id)->first();
            if (null !== $objectGroup) {
                return $objectGroup;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
