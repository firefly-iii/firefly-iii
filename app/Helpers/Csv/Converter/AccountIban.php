<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 05:49
 */

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Class AccountIban
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);
        } else {
            // find or create new account:
            $accountType = AccountType::where('type', 'Asset account')->first();
            $account     = Account::firstOrCreateEncrypted(
                [
                    'name'            => $this->value,
                    //'iban'            => $this->value,
                    'user_id'         => Auth::user()->id,
                    'account_type_id' => $accountType->id
                ]
            );
        }

        return $account;
    }
}