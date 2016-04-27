<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;

/**
 * Class CurrencyId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CurrencyId extends BasicConverter implements ConverterInterface
{

    /**
     * @return TransactionCurrency
     */
    public function convert(): TransactionCurrency
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $currency   = $repository->find($value);

        return $currency;
    }
}
