<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Class AssetAccountIban
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AssetAccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account
     */
    public function convert(): Account
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = $repository->find(intval($this->mapped[$this->index][$this->value]));
            Log::debug('Found mapped account for value "' . $this->value . '". It is account #' . $account->id);

            return $account;
        }

        if (strlen($this->value) > 0) {
            // find or create new account:
            $set = $repository->getAccounts(['Default account', 'Asset account']);
            /** @var Account $entry */
            foreach ($set as $entry) {
                if ($entry->iban == $this->value) {
                    Log::debug('Found an account with the same IBAN ("' . $this->value . '"). It is account #' . $entry->id);

                    return $entry;
                }
            }

            Log::debug('Found no account with the same IBAN ("' . $this->value . '"), so will create a new one.');

            // create it if doesn't exist.
            $accountData = [
                'name'                   => $this->value,
                'accountType'            => 'asset',
                'virtualBalance'         => 0,
                'virtualBalanceCurrency' => 1, // hard coded.
                'active'                 => true,
                'user'                   => Auth::user()->id,
                'iban'                   => $this->value,
                'accountNumber'          => $this->value,
                'accountRole'            => null,
                'openingBalance'         => 0,
                'openingBalanceDate'     => new Carbon,
                'openingBalanceCurrency' => 1, // hard coded.
            ];

            $account = $repository->store($accountData);

            return $account;
        }

        return new Account;
    }
}
