<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Preference
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Models
 */
class Preference extends Model
{

    protected $fillable = ['user_id', 'data', 'name'];

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
