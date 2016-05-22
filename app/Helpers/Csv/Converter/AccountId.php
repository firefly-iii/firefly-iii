<?php
/**
 * AccountId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;

/**
 * Class AccountId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AccountId extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account
     */
    public function convert(): Account
    {

        $crud    = app('FireflyIII\Crud\Account\AccountCrudInterface');
        $var     = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $account = $crud->find($var);

        return $account;
    }
}
