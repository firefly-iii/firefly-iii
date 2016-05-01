<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

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
    public function convert(): Account
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $account    = $repository->find($value);

        return $account;
    }
}
