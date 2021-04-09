<?php
/**
 * Attachment.php
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Attachment
 *
 * @property int                                  $id
 * @property \Illuminate\Support\Carbon|null      $created_at
 * @property \Illuminate\Support\Carbon|null      $updated_at
 * @property \Illuminate\Support\Carbon|null      $deleted_at
 * @property int                                  $user_id
 * @property int                                  $attachable_id
 * @property string                               $attachable_type
 * @property bool                                 $file_exists
 * @property string                               $md5
 * @property string                               $filename
 * @property string|null                          $title
 * @property string|null                          $description
 * @property string                               $mime
 * @property int                                  $size
 * @property bool                                 $uploaded
 * @property string                               $notes_text
 * @property-read Model|\Eloquent                 $attachable
 * @property Collection|\FireflyIII\Models\Note[] $notes
 * @property-read int|null                        $notes_count
 * @property-read User                            $user
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment newQuery()
 * @method static Builder|Attachment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUploaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUserId($value)
 * @method static Builder|Attachment withTrashed()
 * @method static Builder|Attachment withoutTrashed()
 * @mixin Eloquent
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
