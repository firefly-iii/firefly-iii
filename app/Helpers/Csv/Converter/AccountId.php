<?php
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
