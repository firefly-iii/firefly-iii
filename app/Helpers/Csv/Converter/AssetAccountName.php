<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;

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
        $set = Auth::user()->accounts()->accountTypeIn(['Asset account', 'Default account'])->get();
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $this->value) {
                return $entry;
            }
        }

        // create it if doesnt exist.

        $repository  = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $accountData = [
            'name'                   => $this->value,
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'virtualBalanceCurrency' => 1, // hard coded.
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'iban'                   => null,
            'accountNumber'          => $this->value,
            'accountRole'            => null,
            'openingBalance'         => 0,
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => 1, // hard coded.

        ];

        $account = $repository->store($accountData);

        return $account;
    }
}
