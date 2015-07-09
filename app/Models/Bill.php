<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bill
 *
 * @package FireflyIII\Models
 */
class Bill extends Model
{

    protected $fillable
        = ['name', 'match', 'amount_min', 'match_encrypted', 'name_encrypted', 'user_id', 'amount_max', 'date', 'repeat_freq', 'skip', 'automatch', 'active',];

    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];


    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getMatchAttribute($value)
    {

        if (intval($this->match_encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->name_encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param $value
     */
    public function setAmountMaxAttribute($value)
    {
        $this->attributes['amount_max'] = strval(round($value, 2));
    }

    /**
     * @param $value
     */
    public function setAmountMinAttribute($value)
    {
        $this->attributes['amount_min'] = strval(round($value, 2));
    }

    /**
     * @param $value
     */
    public function setMatchAttribute($value)
    {
        $this->attributes['match']           = Crypt::encrypt($value);
        $this->attributes['match_encrypted'] = true;
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']           = Crypt::encrypt($value);
        $this->attributes['name_encrypted'] = true;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


}
