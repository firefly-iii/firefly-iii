<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Budget
 * @package FireflyIII\Models
 */
class Budget extends Model
{

    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'active'];
    protected $hidden   = ['encrypted'];

    /**
     * @param array $fields
     *
     * @return Budget
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query  = Budget::orderBy('id');
        $search = $fields;
        unset($search['name']);
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['budgets.*']);
        /** @var Budget $budget */
        foreach ($set as $budget) {
            if ($budget->name == $fields['name']) {
                return $budget;
            }
        }
        // create it!
        $budget = Budget::create($fields);

        return $budget;

    }

    /**
     *
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
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function limitrepetitions()
    {
        return $this->hasManyThrough('FireflyIII\Models\LimitRepetition', 'FireflyIII\Models\BudgetLimit', 'budget_id');
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


}
