<?php

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Attachment
 *
 * @package FireflyIII\Models
 * @property integer               $id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property string                $deleted_at
 * @property integer               $attachable_id
 * @property string                $attachable_type
 * @property integer               $user_id
 * @property string                $md5
 * @property string                $filename
 * @property string                $mime
 * @property integer               $size
 * @property boolean               $uploaded
 * @property-read \                $attachable
 * @property-read \FireflyIII\User $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereMd5($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereFilename($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereMime($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereSize($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereUploaded($value)
 * @property string                $title
 * @property string                $description
 * @property string                $notes
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Attachment whereNotes($value)
 */
class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'title', 'notes', 'description', 'size', 'uploaded'];

    /**
     * Get all of the owning imageable models.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getFilenameAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param string $value
     */
    public function setFilenameAttribute($value)
    {
        $this->attributes['filename'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getMimeAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param string $value
     */
    public function setMimeAttribute($value)
    {
        $this->attributes['mime'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getTitleAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param string $value
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getDescriptionAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param string $value
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getNotesAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param string $value
     */
    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = Crypt::encrypt($value);
    }

}