<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Transaction
 *
 * @property integer                 $id
 * @property Carbon                  $created_at
 * @property Carbon                  $updated_at
 * @property Carbon                  $deleted_at
 * @property integer                 $account_id
 * @property integer                 $transaction_journal_id
 * @property string                  $description
 * @property float                   $amount
 * @property-read Account            $account
 * @property-read TransactionJournal $transactionJournal
 * @method static Builder|Transaction after($date)
 * @method static Builder|Transaction before($date)
 * @property float $before
 * @property float $after
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
            'amount'                 => 'required|numeric',
        ];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];

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
