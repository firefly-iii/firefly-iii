<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

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
    public function convert(): Account
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = $repository->find(intval($this->mapped[$this->index][$this->value]));

            return $account;
        }

        $set = $repository->getAccountsByType(['Default account', 'Asset account']);
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $this->value) {
                return $entry;
            }
        }
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
