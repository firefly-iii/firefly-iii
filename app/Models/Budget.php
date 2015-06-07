<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Budget
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Models
 * @property integer                                                                               $id
 * @property \Carbon\Carbon                                                                        $created_at
 * @property \Carbon\Carbon                                                                        $updated_at
 * @property \Carbon\Carbon                                                                        $deleted_at
 * @property string                                                                                $name
 * @property integer                                                                               $user_id
 * @property boolean                                                                               $active
 * @property boolean                                                                               $encrypted
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\BudgetLimit[]        $budgetlimits
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionjournals
 * @property-read \FireflyIII\User                                                                 $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereEncrypted($value)
 */
class Budget extends Model
{

    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'active'];
    protected $hidden   = ['encrypted'];

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
