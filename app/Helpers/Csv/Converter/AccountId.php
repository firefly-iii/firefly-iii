<?php
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
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
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);
        } else {
            $account = Auth::user()->accounts()->find($this->value);
        }

        return $account;
    }
}