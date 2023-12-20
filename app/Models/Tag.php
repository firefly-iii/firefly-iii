<?php

/**
 * Tag.php
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Tag
 *
 * @property int                             $id
 * @property null|Carbon                     $created_at
 * @property null|Carbon                     $updated_at
 * @property null|Carbon                     $deleted_at
 * @property int                             $user_id
 * @property string                          $tag
 * @property string                          $tagMode
 * @property null|Carbon                     $date
 * @property null|string                     $description
 * @property null|float                      $latitude
 * @property null|float                      $longitude
 * @property null|int                        $zoomLevel
 * @property Attachment[]|Collection         $attachments
 * @property null|int                        $attachments_count
 * @property Collection|Location[]           $locations
 * @property null|int                        $locations_count
 * @property Collection|TransactionJournal[] $transactionJournals
 * @property null|int                        $transaction_journals_count
 * @property User                            $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method static Builder|Tag                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereTagMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereZoomLevel($value)
 * @method static Builder|Tag                               withTrashed()
 * @method static Builder|Tag                               withoutTrashed()
 *
 * @property int $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereUserGroupId($value)
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

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

    protected $fillable = ['user_id', 'user_group_id', 'tag', 'date', 'description', 'tagMode'];

    protected $hidden = ['zoomLevel', 'latitude', 'longitude'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $tagId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|Tag $tag */
            $tag = $user->tags()->find($tagId);
            if (null !== $tag) {
                return $tag;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class);
    }
}
