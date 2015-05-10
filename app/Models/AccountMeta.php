<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Class AccountMeta
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Models
 */
class AccountMeta extends Model
{

    use ValidatingTrait;
    protected $fillable = ['account_id', 'name', 'data'];
    protected $rules
                        = [
            'account_id' => 'required|exists:accounts,id',
            'name'       => 'required|between:1,100',
            'data'       => 'required'
        ];
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

}
