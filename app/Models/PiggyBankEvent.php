<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\PiggyBankEvent
 *
 * @property integer                 $id
 * @property Carbon                  $created_at
 * @property Carbon                  $updated_at
 * @property integer                 $piggy_bank_id
 * @property integer                 $transaction_journal_id
 * @property Carbon                  $date
 * @property float                   $amount
 * @property PiggyBank               $piggyBank
 * @property-read TransactionJournal $transactionJournal
 */
class PiggyBankEvent extends Model
{

    protected $dates    = ['created_at', 'updated_at', 'date'];
    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'amount'];
    protected $hidden   = ['amount_encrypted'];

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
        $this->attributes['amount'] = strval(round($value, 2));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }

}
