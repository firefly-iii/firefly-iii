<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PiggyBankEvent
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Models
 */
class PiggyBankEvent extends Model
{

    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'amount'];

    /**
     * @param $value
     *
     * @return float|int
     */
    public function getAmountAttribute($value)
    {
        if (is_null($this->amount_encrypted)) {
            return $value;
        }
        $value = intval(Crypt::decrypt($this->amount_encrypted));
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
    }

    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        // save in cents:
        $value                                = intval($value * 100);
        $this->attributes['amount_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount']           = ($value / 100);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }

}
