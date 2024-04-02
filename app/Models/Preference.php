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

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Preference
 *
 * @property int                        $id
 * @property null|Carbon                $created_at
 * @property null|Carbon                $updated_at
 * @property int                        $user_id
 * @property string                     $name
 * @property null|array|bool|int|string $data
 * @property User                       $user
 *
 * @method static Builder|Preference newModelQuery()
 * @method static Builder|Preference newQuery()
 * @method static Builder|Preference query()
 * @method static Builder|Preference whereCreatedAt($value)
 * @method static Builder|Preference whereData($value)
 * @method static Builder|Preference whereId($value)
 * @method static Builder|Preference whereName($value)
 * @method static Builder|Preference whereUpdatedAt($value)
 * @method static Builder|Preference whereUserId($value)
 *
 * @property mixed $user_group_id
 *
 * @mixin Eloquent
 */
class Preference extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'data'       => 'array',
        ];

    protected $fillable = ['user_id', 'data', 'name'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            /** @var User $user */
            $user        = auth()->user();

            // some preferences do not have an administration ID.
            // some need it, to make sure the correct one is selected.
            $userGroupId = (int)$user->user_group_id;
            $userGroupId = 0 === $userGroupId ? null : $userGroupId;

            /** @var null|Preference $preference */
            $preference  = null;
            $items       = config('firefly.admin_specific_prefs');
            if (null !== $userGroupId && in_array($value, $items, true)) {
                // find a preference with a specific user_group_id
                $preference = $user->preferences()->where('user_group_id', $userGroupId)->where('name', $value)->first();
            }
            if (!in_array($value, $items, true)) {
                // find any one.
                $preference = $user->preferences()->where('name', $value)->first();
            }

            // try again with ID, but this time don't care about the preferred user_group_id
            if (null === $preference) {
                $preference = $user->preferences()->where('id', (int)$value)->first();
            }
            if (null !== $preference) {
                return $preference;
            }
            $default     = config('firefly.default_preferences');
            if (array_key_exists($value, $default)) {
                $preference                = new self();
                $preference->name          = $value;
                $preference->data          = $default[$value];
                $preference->user_id       = (int)$user->id;
                $preference->user_group_id = in_array($value, $items, true) ? $userGroupId : null;
                $preference->save();

                return $preference;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
