<?php
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class AccountMeta
 */
class AccountMeta extends Eloquent
{
    use ValidatingTrait;
    /**
     * @var array
     */
    public static $rules
        = [
            'account_id' => 'numeric|required|exists:accounts,id',
            'name'       => 'required|between:1,250',
            'data'       => 'required'
        ];
    /**
     * @var array
     */
    // @codingStandardsIgnoreStart
    protected $fillable = ['account_id', 'name', 'date'];
    protected $table    = 'account_meta';
    // @codingStandardsIgnoreEnd

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
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