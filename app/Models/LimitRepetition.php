<?php namespace FireflyIII\Models;

use Auth;
use Crypt;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LimitRepetition
 *
 * @package FireflyIII\Models
 */
class LimitRepetition extends Model
{

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetLimit()
    {
        return $this->belongsTo('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * @return float
     */
    public function spentInRepetition()
    {
        $sum = DB::table('transactions')
                 ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                 ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                 ->leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budget_transaction_journal.budget_id')
                 ->leftJoin('limit_repetitions', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                 ->where('transaction_journals.date', '>=', $this->startdate->format('Y-m-d'))
                 ->where('transaction_journals.date', '<=', $this->enddate->format('Y-m-d'))
                 ->where('transaction_journals.user_id', Auth::user()->id)
                 ->whereNull('transactions.deleted_at')
                 ->where('transactions.amount', '>', 0)
                 ->where('limit_repetitions.id', '=', $this->id)
                 ->sum('transactions.amount');

        return floatval($sum);
    }

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
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        // save in cents:
        $value                                = intval($value * 100);
        $this->attributes['amount_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount']           = ($value / 100);
    }

}
