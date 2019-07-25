<?php
/**
 * Note.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Note.
 *
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $text
 * @property string $title
 * @property int    $noteable_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $noteable_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Note[] $noteable
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereNoteableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereNoteableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Note whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note withoutTrashed()
 * @mixin \Eloquent
 */
class Note extends Model
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
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['title', 'text', 'noteable_id', 'noteable_type'];

    /**
     * @codeCoverageIgnore
     *
     * Get all of the owning noteable models.
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param $value
     * @codeCoverageIgnore
     */
    public function setTextAttribute($value): void
    {
        $this->attributes['text'] = e($value);
    }
}
