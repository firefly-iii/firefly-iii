<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;

/**
 * Class CurrencyName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CurrencyName extends BasicConverter implements ConverterInterface
{

    /**
     * @return TransactionCurrency
     */
    public function convert(): TransactionCurrency
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        if (isset($this->mapped[$this->index][$this->value])) {
            $currency = $repository->find($this->mapped[$this->index][$this->value]);

            return $currency;
        }
        $currency = $repository->findByName($this->value);
        

        return $currency;
    }
}
