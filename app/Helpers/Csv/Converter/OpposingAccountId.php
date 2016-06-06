<?php
/**
 * OpposingAccountId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;

/**
 * Class OpposingAccountId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class OpposingAccountId extends BasicConverter implements ConverterInterface
{


    /**
     * @return Account
     */
    public function convert(): Account
    {
        $crud    = app('FireflyIII\Crud\Account\AccountCrudInterface');
        $value   = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $account = $crud->find($value);

        return $account;
    }
}
