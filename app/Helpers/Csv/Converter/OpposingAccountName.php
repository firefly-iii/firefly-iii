<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

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
        /** @var AccountRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        if (isset($this->mapped[$this->index][$this->value])) {
            $account = $repository->find($this->mapped[$this->index][$this->value]);

            return $account;
        } else {
            return $this->value;
        }
    }
}
