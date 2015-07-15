<?php
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Account;
use Log;

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

            /** @var Account $account */
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);
        } else {

            /** @var Account $account */
            $account = Auth::user()->accounts()->find($this->value);

            if (!is_null($account)) {
                Log::debug('Found ' . $account->accountType->type . ' named "******" with ID: ' . $this->value . ' (not mapped) ');
            }
        }

        return $account;
    }
}
