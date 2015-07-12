<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Class AccountMeta
 *
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property integer $account_id 
 * @property string $name 
 * @property string $data 
 * @property-read \FireflyIII\Models\Account $account 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountMeta whereData($value)
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
