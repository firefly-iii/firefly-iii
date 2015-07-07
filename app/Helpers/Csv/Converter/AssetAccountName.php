<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Class AssetAccountName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AssetAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);

            return $account;
        }
        // find or create new account:
        $accountType = AccountType::where('type', 'Asset account')->first();
        $set         = Auth::user()->accounts()->accountTypeIn(['Asset account', 'Default account'])->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $this->value) {
                return $entry;
            }
        }

        // create it if doesnt exist.
        $account = Account::firstOrCreateEncrypted(
            [
                'name'            => $this->value,
                'iban'            => '',
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'active'          => 1,
            ]
        );

        return $account;
    }
}