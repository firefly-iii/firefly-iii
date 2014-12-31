<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
use Watson\Validating\ValidatingTrait;

/**
 * Class Preference
 */
class Preference extends Eloquent
{
    use ValidatingTrait;
    protected $fillable = ['name', 'data', 'user_id'];
    protected $rules
                        = ['user_id' => 'required|exists:users,id', 'name' => 'required|between:1,255', 'data' => 'required'];

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

} 