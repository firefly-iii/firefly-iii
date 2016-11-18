<?php
/**
 * AccountType.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    const DEFAULT         = 'Default account';
    const CASH            = 'Cash account';
    const ASSET           = 'Asset account';
    const EXPENSE         = 'Expense account';
    const REVENUE         = 'Revenue account';
    const INITIAL_BALANCE = 'Initial balance account';
    const BENEFICIARY     = 'Beneficiary account';
    const IMPORT          = 'Import account';


    protected $dates = ['created_at', 'updated_at'];

    //
    /**
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }
}
