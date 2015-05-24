<?php namespace FireflyIII\Models;

use Crypt;
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
     *
     * @return float|int
     */
    public function getNameAttribute($value)
    {
        if (is_null($this->name_encrypted)) {
            return $value;
        }
        $value = Crypt::decrypt($this->name_encrypted);

        return $value;
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
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name_encrypted'] = Crypt::encrypt($value);
        $this->attributes['name']           = $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
