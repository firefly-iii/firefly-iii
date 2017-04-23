<?php
/**
 * Note.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

/**
 * Class Note
 *
 * @package FireflyIII\Models
 */
class Note extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
        ];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['title', 'text'];

    /**
     * @return string
     */
    public function getMarkdownAttribute(): string
    {
        $converter = new CommonMarkConverter;

        return $converter->convertToHtml($this->text);
    }

    /**
     * Get all of the owning noteable models. Currently only piggy bank
     */
    public function noteable()
    {
        return $this->morphTo();
    }

}
