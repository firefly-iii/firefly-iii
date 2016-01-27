<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
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
    public function convert()
    {
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);

        } else {
            $account = Auth::user()->accounts()->find($this->value);
        }

        return $account;

    }
}
