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

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;


/**
 * FireflyIII\Models\Note
 *
 * @property integer                                            $id
 * @property \Carbon\Carbon                                     $created_at
 * @property \Carbon\Carbon                                     $updated_at
 * @property string                                             $deleted_at
 * @property integer                                            $noteable_id
 * @property string                                             $noteable_type
 * @property string                                             $title
 * @property string                                             $text
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $noteable
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereNoteableId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereNoteableType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Note whereText($value)
 * @mixin \Eloquent
 */
class Note extends Model
{
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['title', 'text'];


    /**
     * @param $value
     *
     * @return string
     */
    public function getMarkdownAttribute(): string
    {
        $converter  = new CommonMarkConverter;
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