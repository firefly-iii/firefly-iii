<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Account;

/**
 * Class OpposingAccountName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class OpposingAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * If mapped, return account. Otherwise, only return the name itself.
     *
     * @return Account|string
     */
    public function convert()
    {
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);

            return $account;
        } else {
            return $this->value;
        }
    }
}
