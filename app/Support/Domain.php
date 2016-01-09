<?php
/**
 * Domain.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support;

/**
 * Class Domain
 *
 * @package FireflyIII\Support
 */
class Domain
{
    /**
     * @return array
     */
    public static function getBindables()
    {
        return [
            // models
            'account'         => 'FireflyIII\Models\Account',
            'attachment'      => 'FireflyIII\Models\Attachment',
            'bill'            => 'FireflyIII\Models\Bill',
            'budget'          => 'FireflyIII\Models\Budget',
            'category'        => 'FireflyIII\Models\Category',
            'currency'        => 'FireflyIII\Models\TransactionCurrency',
            'limitrepetition' => 'FireflyIII\Models\LimitRepetition',
            'piggyBank'       => 'FireflyIII\Models\PiggyBank',
            'tj'              => 'FireflyIII\Models\TransactionJournal',
            'tag'             => 'FireflyIII\Models\Tag',
            // lists
            'accountList'     => 'FireflyIII\Support\Binder\AccountList',
            'budgetList'      => 'FireflyIII\Support\Binder\BudgetList',
            'categoryList'    => 'FireflyIII\Support\Binder\CategoryList',

            // others
            'start_date'      => 'FireflyIII\Support\Binder\Date',
            'end_date'        => 'FireflyIII\Support\Binder\Date'
        ];


    }

}