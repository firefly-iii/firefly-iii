<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\AccountMeta
 *
 * @property integer      $id
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 * @property integer      $account_id
 * @property string       $name
 * @property string       $data
 * @property-read Account $account
 */
class AccountMeta extends Model
{

    protected $dates    = ['created_at', 'updated_at'];
    protected $fillable = ['account_id', 'name', 'data'];
    protected $table    = 'account_meta';

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }


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
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

}
