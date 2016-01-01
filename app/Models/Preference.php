<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\Preference
 *
 * @property integer   $id
 * @property Carbon    $created_at
 * @property Carbon    $updated_at
 * @property integer   $user_id
 * @property string    $name
 * @property string    $name_encrypted
 * @property string    $data
 * @property string    $data_encrypted
 * @property-read User $user
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
