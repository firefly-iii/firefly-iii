<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Preference
 *
 * @package FireflyIII\Models
 */
class Preference extends Model
{

    protected $fillable = ['user_id', 'data', 'name'];
    protected $hidden   = ['data_encrypted', 'name_encrypted'];

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        if (is_null($this->data_encrypted)) {
            return json_decode($value);
        }
        $data = Crypt::decrypt($this->data_encrypted);

        return json_decode($data);
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
        $this->attributes['data']           = '';
        $this->attributes['data_encrypted'] = Crypt::encrypt(json_encode($value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
