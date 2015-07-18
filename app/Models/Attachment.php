<?php

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Attachment
 *
 * @package FireflyIII\Models
 */
class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'size', 'uploaded'];

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

}