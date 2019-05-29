<?php
/**
 * AccountType.php
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AccountType.
 *
 * @property string $type
 * @method whereType(string $type)
 * @property int    $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[] $accounts
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AccountType extends Model
{
    /** @var string */
    public const DEFAULT = 'Default account';
    /** @var string */
    public const CASH = 'Cash account';
    /** @var string */
    public const ASSET = 'Asset account';
    /** @var string */
    public const EXPENSE = 'Expense account';
    /** @var string */
    public const REVENUE = 'Revenue account';
    /** @var string */
    public const INITIAL_BALANCE = 'Initial balance account';
    /** @var string */
    public const BENEFICIARY = 'Beneficiary account';
    /** @var string */
    public const IMPORT = 'Import account';
    /** @var string */
    public const RECONCILIATION = 'Reconciliation account';
    /** @var string */
    public const LOAN = 'Loan';
    /** @var string */
    public const DEBT = 'Debt';
    /** @var string */
    public const MORTGAGE = 'Mortgage';
    /** @var string */
    public const CREDITCARD = 'Credit card';
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['type'];

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
