<?php
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class Bill
 */
class Bill extends Eloquent
{

    use ValidatingTrait;
    protected $rules
        = [
            'user_id'     => 'required|exists:users,id',
            'name'        => 'required|between:1,255|min:1',
            'match'       => 'required',
            'amount_max'  => 'required|between:0,65536',
            'amount_min'  => 'required|between:0,65536',
            'date'        => 'required|date',
            'active'      => 'between:0,1',
            'automatch'   => 'between:0,1',
            'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly',
            'skip'        => 'required|between:0,31',];
    protected $fillable = ['user_id', 'name', 'match', 'amount_min', 'amount_max', 'date', 'repeat_freq', 'skip', 'active', 'automatch'];
    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }



    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
} 
