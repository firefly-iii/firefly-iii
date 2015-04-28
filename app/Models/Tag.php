<?php

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tag
 *
 * @package FireflyIII\Models
 */
class Tag extends Model
{

    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude','zoomLevel','tagMode'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return Crypt::decrypt($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getTagAttribute($value)
    {
        return Crypt::decrypt($value);
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}