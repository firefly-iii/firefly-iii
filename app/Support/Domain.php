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
            'account'     => 'FireflyIII\Models\Account',
            'accountList' => 'FireflyIII\Support\Binder\AccountList',
            'start_date'  => 'FireflyIII\Support\Binder\Date',
            'end_date'    => 'FireflyIII\Support\Binder\Date',
        ];
    }

}