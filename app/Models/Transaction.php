<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Class Transaction
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property integer $account_id
 * @property integer $transaction_journal_id
 * @property string $description
 * @property float $amount
 * @property string $amount_encrypted
 * @property-read \FireflyIII\Models\Account $account
 * @property-read \FireflyIII\Models\TransactionJournal $transactionJournal
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereAmountEncrypted($value)
 * @method static \FireflyIII\Models\Transaction after($date)
 * @method static \FireflyIII\Models\Transaction before($date)
 * @property mixed before
 * @property mixed after
 */
class Transaction extends Model
{

    protected $fillable = ['account_id', 'transaction_journal_id', 'description', 'amount'];
    protected $hidden   = ['encrypted'];
    protected $rules
                        = [
            'account_id'             => 'required|exists:accounts,id',
            'transaction_journal_id' => 'required|exists:transaction_journals,id',
            'description'            => 'between:1,255',
            'amount'                 => 'required|numeric'
        ];
    use SoftDeletes, ValidatingTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * @param $value
     *
     * @return float|int
     */
    public function getAmountAttribute($value)
    {
        return $value;
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeAfter(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeBefore(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 00:00:00'));
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
