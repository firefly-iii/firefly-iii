<?php
/**
 * Tag.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use FireflyIII\Support\Models\TagSupport;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Tag
 *
 * @property integer                                                            $id
 * @property \Carbon\Carbon                                                     $created_at
 * @property \Carbon\Carbon                                                     $updated_at
 * @property string                                                             $deleted_at
 * @property integer                                                            $user_id
 * @property string                                                             $tag
 * @property string                                                             $tagMode
 * @property \Carbon\Carbon                                                     $date
 * @property string                                                             $description
 * @property float                                                              $latitude
 * @property float                                                              $longitude
 * @property integer                                                            $zoomLevel
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[] $transactionjournals
 * @property-read \FireflyIII\User                                              $user
 * @property int                                                                $account_id
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereTag($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereTagMode($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereLatitude($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereLongitude($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Tag whereZoomLevel($value)
 * @mixin \Eloquent
 */
class Tag extends TagSupport
{
    protected $dates    = ['created_at', 'updated_at', 'date'];
    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude', 'zoomLevel', 'tagMode'];
    protected $rules    = ['tag' => 'required|between:1,200',];

    use ValidatingTrait;

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

        $query = Tag::orderBy('id');
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
        $tag                   = Tag::create($fields);

        return $tag;

    }

    /**
     * @param Tag $value
     *
     * @return Tag
     */
    public static function routeBinder(Tag $value)
    {
        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
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
            bcadd($sum, TransactionJournal::amount($journal));
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
