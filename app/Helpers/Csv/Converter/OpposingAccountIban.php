<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class OpposingAccountIban
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class OpposingAccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * If mapped, return account. Otherwise, only return the name itself.
     *
     * @return Account|string
     */
    public function convert()
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        if (isset($this->mapped[$this->index][$this->value])) {
            $account = $repository->find($this->mapped[$this->index][$this->value]);

            return $account;
        }

        return $this->findAccount($repository);
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return Account|string
     */
    private function findAccount(AccountRepositoryInterface $repository)
    {
        if (strlen($this->value) > 0) {

            $set = $repository->getAccounts([]);
            /** @var Account $account */
            foreach ($set as $account) {
                if ($account->iban == $this->value) {

                    return $account;
                }
            }
        }

        return $this->value;
    }

}
