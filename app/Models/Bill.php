<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * @codeCoverageIgnore
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
     * @param $value
     *
     * @return float|int
     */
    public function getAmountMaxAttribute($value)
    {
        if (is_null($this->amount_max_encrypted)) {
            return $value;
        }
        $value = intval(Crypt::decrypt($this->amount_max_encrypted));
        $value = $value / 100;

        return $value;
    }

    /**
     *
     * @param $value
     *
     * @return float|int
     */
    public function getAmountMinAttribute($value)
    {
        if (is_null($this->amount_min_encrypted)) {
            return $value;
        }
        $value = intval(Crypt::decrypt($this->amount_min_encrypted));
        $value = $value / 100;

        return $value;
    }

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
        // save in cents:
        $value                                    = intval($value * 100);
        $this->attributes['amount_max_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount_max']           = ($value / 100);
    }

    /**
     * @param $value
     */
    public function setAmountMinAttribute($value)
    {
        // save in cents:
        $value                                    = intval($value * 100);
        $this->attributes['amount_min_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount_min']           = ($value / 100);
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
