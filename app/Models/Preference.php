<?php
/**
 * Preference.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use Eloquent;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Preference
 *
 * @property int                             $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int                             $user_id
 * @property string                          $name
 * @property int|string|array|null           $data
 * @property-read User                       $user
 * @method static Builder|Preference newModelQuery()
 * @method static Builder|Preference newQuery()
 * @method static Builder|Preference query()
 * @method static Builder|Preference whereCreatedAt($value)
 * @method static Builder|Preference whereData($value)
 * @method static Builder|Preference whereId($value)
 * @method static Builder|Preference whereName($value)
 * @method static Builder|Preference whereUpdatedAt($value)
 * @method static Builder|Preference whereUserId($value)
 * @mixin Eloquent
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
            /** @var Preference|null $preference */
            $preference = $user->preferences()->where('name', $value)->first();
            if (null !== $preference) {
                return $preference;
            }
            $default = config('firefly.default_preferences');
            if (array_key_exists($value, $default)) {
                $preference          = new Preference;
                $preference->name    = $value;
                $preference->data    = $default[$value];
                $preference->user_id = $user->id;
                $preference->save();

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
