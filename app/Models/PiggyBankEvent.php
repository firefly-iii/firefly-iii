<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PiggyBankEvent
 *
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property integer $piggy_bank_id 
 * @property integer $transaction_journal_id 
 * @property \Carbon\Carbon $date 
 * @property float $amount 
 * @property-read \FireflyIII\Models\PiggyBank $piggyBank 
 * @property-read \FireflyIII\Models\TransactionJournal $transactionJournal 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent wherePiggyBankId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankEvent whereAmount($value)
 */
class PiggyBankEvent extends Model
{

    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'amount'];
    protected $hidden   = ['amount_encrypted'];

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
