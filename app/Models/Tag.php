<?php

namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Tag
 *
 * @property integer                              $id
 * @property Carbon                               $created_at
 * @property Carbon                               $updated_at
 * @property string                               $deleted_at
 * @property integer                              $user_id
 * @property string                               $tag
 * @property string                               $tagMode
 * @property Carbon                               $date
 * @property string                               $description
 * @property float                                $latitude
 * @property float                                $longitude
 * @property integer                              $zoomLevel
 * @property-read Collection|TransactionJournal[] $transactionjournals
 * @property-read User                            $user
 */
class Tag extends Model
{
    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude', 'zoomLevel', 'tagMode'];
    protected $dates    = ['created_at', 'updated_at', 'date'];

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
        $fields['description'] = isset($fields['description']) && !is_null($fields['description']) ? $fields['description'] : '';
        $tag                   = Tag::create($fields);

        return $tag;

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
        foreach ($this->transactionjournals()->get() as $journal) {
            $count              = $journal->tags()->count();
            $journal->tag_count = $count;
            $journal->save();
        }

        return parent::save($options);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setTagAttribute($value)
    {
        $this->attributes['tag'] = Crypt::encrypt($value);
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


}
