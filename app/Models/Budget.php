<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Budget
 *
 * @property integer                              $id
 * @property Carbon                               $created_at
 * @property Carbon                               $updated_at
 * @property Carbon                               $deleted_at
 * @property string                               $name
 * @property integer                              $user_id
 * @property boolean                              $active
 * @property boolean                              $encrypted
 * @property-read Collection|BudgetLimit[]        $budgetlimits
 * @property-read Collection|TransactionJournal[] $transactionjournals
 * @property-read User                            $user
 * @property string                               $dateFormatted
 * @property string                               $budgeted
 * @property float                                $amount
 * @property Carbon                               $date
 */
class Budget extends Model
{

    use SoftDeletes;

    protected $dates    = ['created_at', 'updated_at', 'deleted_at', 'startdate', 'enddate'];
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
     * @param Budget $value
     *
     * @return Budget
     */
    public static function routeBinder(Budget $value)
    {
        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
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
