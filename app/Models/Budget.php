<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Crypt;

/**
 * Class Budget
 *
 * @package FireflyIII\Models
 */
class Budget extends Model
{

    use SoftDeletes;

    protected $fillable = ['user_id', 'name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetlimits()
    {
        return $this->hasMany('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function limitrepetitions()
    {
        return $this->hasManyThrough('FireflyIII\Models\LimitRepetition', 'FireflyIII\Models\BudgetLimit', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }
    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        // @codeCoverageIgnoreStart
        return $value;
        // @codeCoverageIgnoreEnd
    }


}
