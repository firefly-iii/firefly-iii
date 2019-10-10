<?php
/**
 * Attachment.php
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Attachment.
 *
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $attachable_type
 * @property string $md5
 * @property string $filename
 * @property string $title
 * @property string $description
 * @property string $notes
 * @property string $mime
 * @property int    $size
 * @property User   $user
 * @property bool   $uploaded
 * @property bool   file_exists
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @property int $attachable_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[] $attachable
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereUploaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Attachment whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment withoutTrashed()
 * @mixin \Eloquent
 */
class Attachment extends Model
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
            'uploaded'   => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'title', 'description', 'size', 'uploaded'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Attachment
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Attachment
    {
        if (auth()->check()) {
            $attachmentId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Attachment $attachment */
            $attachment = $user->attachments()->find($attachmentId);
            if (null !== $attachment) {
                return $attachment;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * Get all of the owning attachable models.
     *
     * @codeCoverageIgnore
     *
     * @return MorphTo
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Returns the expected filename for this attachment.
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function fileName(): string
    {
        return sprintf('at-%s.data', (string)$this->id);
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
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
