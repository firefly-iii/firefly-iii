<?php
/**
 * CurrencyCode.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;

/**
 * Class CurrencyCode
 *
 * @package FireflyIII\Import\Converter
 */
class CurrencyCode extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return TransactionCurrency
     */
    public function convert($value): TransactionCurrency
    {
        Log::debug('Going to convert ', ['value' => $value]);

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        if (isset($this->mapping[$value])) {
            Log::debug('Found currency in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $currency = $repository->find(intval($this->mapping[$value]));
            if (!is_null($currency->id)) {
                Log::debug('Found currency by ID', ['id' => $currency->id]);
                $this->setCertainty(100);
                return $currency;
            }
        }

        // not mapped? Still try to find it first:
        $currency = $repository->findByCode($value);
        if (!is_null($currency->id)) {
            Log::debug('Found currency by code', ['id' => $currency->id]);
            $this->setCertainty(100);
            return $currency;
        }
        $currency = $repository->store(
            [
                'name'   => $value,
                'code'   => $value,
                'symbol' => $value,
            ]
        );
        $this->setCertainty(100);

        return $currency;
    }
}