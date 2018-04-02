<?php
/**
 * Attachment.php
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

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Attachment.
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
    /** @var array */
    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'title', 'description', 'size', 'uploaded'];

    /**
     * @param string $value
     *
     * @return Attachment
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value): Attachment
    {
        if (auth()->check()) {
            $attachmentId = (int)$value;
            $attachment   = auth()->user()->attachments()->find($attachmentId);
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
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getDescriptionAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getFilenameAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getMimeAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getTitleAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setDescriptionAttribute(string $value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setFilenameAttribute(string $value)
    {
        $this->attributes['filename'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setMimeAttribute(string $value)
    {
        $this->attributes['mime'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setTitleAttribute(string $value)
    {
        $this->attributes['title'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
