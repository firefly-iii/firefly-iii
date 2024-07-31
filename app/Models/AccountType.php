<?php

/**
 * AccountType.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AccountType extends Model
{
    use ReturnsIntegerIdTrait;

    public const string ASSET            = 'Asset account';
    public const string BENEFICIARY      = 'Beneficiary account';
    public const string CASH             = 'Cash account';
    public const string CREDITCARD       = 'Credit card';
    public const string DEBT             = 'Debt';
    public const string DEFAULT          = 'Default account';
    public const string EXPENSE          = 'Expense account';
    public const string IMPORT           = 'Import account';
    public const string INITIAL_BALANCE  = 'Initial balance account';
    public const string LIABILITY_CREDIT = 'Liability credit account';
    public const string LOAN             = 'Loan';
    public const string MORTGAGE         = 'Mortgage';
    public const string RECONCILIATION   = 'Reconciliation account';
    public const string REVENUE          = 'Revenue account';

    protected $casts
                                         = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    protected $fillable                  = ['type'];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
