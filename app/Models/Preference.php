<?php
/**
 * Preference.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Preference.
 *
 * @property mixed  $data
 * @property string $name
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property int    $id
 * @property User   user
 * @property int $user_id
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Preference whereUserId($value)
 * @mixin \Eloquent
 */
class Preference extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'data'       => 'array',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'data', 'name'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Preference
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Preference
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            /** @var Preference $preference */
            $preference = $user->preferences()->where('name', $value)->first();
            if (null !== $preference) {
                return $preference;
            }
        }
        throw new NotFoundHttpException;
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
