<?php
declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Attachment
 *
 * @property integer               $id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property string                $deleted_at
 * @property integer               $attachable_id
 * @property string                $attachable_type
 * @property integer               $user_id
 * @property string                $md5
 * @property string                $filename
 * @property string                $title
 * @property string                $description
 * @property string                $notes
 * @property string                $mime
 * @property integer               $size
 * @property boolean               $uploaded
 * @property-read Attachment       $attachable
 * @property-read \FireflyIII\User $user
 */
class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'title', 'notes', 'description', 'size', 'uploaded'];

    /**
     * @param Attachment $value
     *
     * @return Attachment
     */
    public static function routeBinder(Attachment $value)
    {
        if (Auth::check()) {

            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * Get all of the owning imageable models.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Returns the expected filename for this attachment.
     *
     * @return string
     */
    public function fileName(): string
    {
        return sprintf('at-%s.data', strval($this->id));
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
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @param string $value
     */
    public function setFilenameAttribute($value)
    {
        $this->attributes['filename'] = Crypt::encrypt($value);
    }

    /**
     * @param string $value
     */
    public function setMimeAttribute($value)
    {
        $this->attributes['mime'] = Crypt::encrypt($value);
    }

    /**
     * @param string $value
     */
    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = Crypt::encrypt($value);
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
