<?php
/**
 * Tag.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Tag
 *
 * @package FireflyIII\Models
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
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
            'date'       => 'date',
            'zoomLevel'  => 'int',

        ];
    protected $dates    = ['created_at', 'updated_at', 'date', 'deleted_at'];
    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude', 'zoomLevel', 'tagMode'];
    protected $rules    = ['tag' => 'required|between:1,200',];


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
            if ($tag->tag == $fields['tag']) {
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
    public static function routeBinder(Tag $value)
    {
        if (auth()->check()) {
            if ($value->user_id == auth()->user()->id) {
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
    public static function tagSum(Tag $tag): string
    {
        $sum = '0';
        /** @var TransactionJournal $journal */
        foreach ($tag->transactionjournals as $journal) {
            bcadd($sum, $journal->amount());
        }

        return $sum;
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }

        return Crypt::decrypt($value);
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function getTagAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * Save the model to the database.
     *
     * @param  array $options
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
     *
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     *
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
