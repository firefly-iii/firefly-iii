<?php
/**
 * Tag.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Tag.
 *
 * @property Collection     $transactionJournals
 * @property string         $tag
 * @property int            $id
 * @property \Carbon\Carbon $date
 * @property int            zoomLevel
 * @property float          latitude
 * @property float          longitude
 * @property string         description
 * @property string         amount_sum
 * @property string         tagMode
 * @property Carbon         created_at
 * @property Carbon         updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @property-read \FireflyIII\User $user
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereTagMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Tag whereZoomLevel($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag withoutTrashed()
 * @mixin \Eloquent
 */
class Tag extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'date'       => 'date',
            'zoomLevel'  => 'int',
            'latitude'   => 'float',
            'longitude'  => 'float',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'tag', 'date', 'description','tagMode'];

    protected $hidden = ['zoomLevel', 'latitude', 'longitude'];

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Tag
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Tag
    {
        if (auth()->check()) {
            $tagId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Tag $tag */
            $tag = $user->tags()->find($tagId);
            if (null !== $tag) {
                return $tag;
            }
        }
        throw new NotFoundHttpException;
    }


    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
