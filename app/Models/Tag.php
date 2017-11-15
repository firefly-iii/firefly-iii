<?php
/**
 * Tag.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Tag.
 */
class Tag extends Model
{
    use ValidatingTrait, SoftDeletes;

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
            'date'       => 'date',
            'zoomLevel'  => 'int',
        ];
    /** @var array */
    protected $dates = ['date'];
    /** @var array */
    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude', 'zoomLevel', 'tagMode'];
    /** @var array */
    protected $rules = ['tag' => 'required|between:1,200'];

    /**
     * @param array $fields
     *
     * @return Tag|null
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the tag:
        unset($fields['tagMode']);
        $search = $fields;
        unset($search['tag']);

        $query = self::orderBy('id');
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['tags.*']);
        /** @var Tag $tag */
        foreach ($set as $tag) {
            if ($tag->tag === $fields['tag']) {
                return $tag;
            }
        }
        // create it!
        $fields['tagMode']     = 'nothing';
        $fields['description'] = $fields['description'] ?? '';
        $tag                   = self::create($fields);

        return $tag;
    }

    /**
     * @param Tag $value
     *
     * @return Tag
     */
    public static function routeBinder(self $value)
    {
        if (auth()->check()) {
            if (intval($value->user_id) === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param Tag $tag
     *
     * @return string
     */
    public static function tagSum(self $tag): string
    {
        $sum = '0';
        /** @var TransactionJournal $journal */
        foreach ($tag->transactionjournals as $journal) {
            bcadd($sum, $journal->amount());
        }

        return $sum;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        if (null === $value) {
            return $value;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getTagAttribute($value)
    {
        if (null === $value) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * Save the model to the database.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        foreach ($this->transactionJournals()->get() as $journal) {
            $count              = $journal->tags()->count();
            $journal->tag_count = $count;
            $journal->save();
        }

        return parent::save($options);
    }

    /**
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @param $value
     */
    public function setTagAttribute($value)
    {
        $this->attributes['tag'] = Crypt::encrypt($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionJournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
