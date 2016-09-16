<?php
/**
 * Budget.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Budget
 *
 * @property integer                                                                            $id
 * @property \Carbon\Carbon                                                                     $created_at
 * @property \Carbon\Carbon                                                                     $updated_at
 * @property \Carbon\Carbon                                                                     $deleted_at
 * @property string                                                                             $name
 * @property integer                                                                            $user_id
 * @property boolean                                                                            $active
 * @property boolean                                                                            $encrypted
 * @property-read \Illuminate\Database\Eloquent\Collection|BudgetLimit[]                        $budgetlimits
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[]                 $transactionjournals
 * @property-read \FireflyIII\User                                                              $user
 * @property string                                                                             $dateFormatted
 * @property string                                                                             $budgeted
 * @property float                                                                              $amount
 * @property \Carbon\Carbon                                                                     $date
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Budget whereEncrypted($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[]     $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\LimitRepetition[] $limitrepetitions
 */
class Budget extends Model
{

    use SoftDeletes, ValidatingTrait;

    protected $dates    = ['created_at', 'updated_at', 'deleted_at', 'startdate', 'enddate'];
    protected $fillable = ['user_id', 'name', 'active'];
    protected $hidden   = ['encrypted'];
    protected $rules    = ['name' => 'required|between:1,200',];

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
        if (auth()->check()) {
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
    public function transactionJournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactions()
    {
        return $this->belongsToMany('FireflyIII\Models\Transaction', 'budget_transaction', 'budget_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }


}
