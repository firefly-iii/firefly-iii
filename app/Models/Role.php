<?php
/**
 * Role.php
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

use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Role.
 *
 * @property int    $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $display_name
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends Model
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
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['name', 'display_name', 'description'];

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
