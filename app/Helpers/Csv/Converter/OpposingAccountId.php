<?php
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
