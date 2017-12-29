<?php
/**
 * Budget.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Budget.
 */
class Budget extends Model
{
    use SoftDeletes, ValidatingTrait;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array */
    protected $fillable = ['user_id', 'name', 'active'];
    /** @var array */
    protected $hidden = ['encrypted'];
    /** @var array */
    protected $rules = ['name' => 'required|between:1,200'];

    /**
     * @param array $fields
     *
     * @return Budget
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query  = self::orderBy('id');
        $search = $fields;
        unset($search['name']);
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['budgets.*']);
        /** @var Budget $budget */
        foreach ($set as $budget) {
            if ($budget->name === $fields['name']) {
                return $budget;
            }
        }
        // create it!
        $budget = self::create($fields);

        return $budget;
    }

    /**
     * @param string $value
     *
     * @return Budget
     */
    public static function routeBinder(string $value): Budget
    {
        if (auth()->check()) {
            $budgetId = intval($value);
            $budget   = auth()->user()->budgets()->find($budgetId);
            if (!is_null($budget)) {
                return $budget;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetlimits()
    {
        return $this->hasMany('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionJournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactions()
    {
        return $this->belongsToMany('FireflyIII\Models\Transaction', 'budget_transaction', 'budget_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
