<?php
/**
 * CurrencyName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;

/**
 * Class CurrencyName
 *
 * @package FireflyIII\Import\Converter
 */
class CurrencyName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return TransactionCurrency
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using CurrencyName', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);

            return new TransactionCurrency;
        }

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $repository->setUser($this->user);

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
        $currency = $repository->findByName($value);
        if (!is_null($currency->id)) {
            Log::debug('Found currency by name ', ['id' => $currency->id]);
            $this->setCertainty(100);

            return $currency;
        }

        // create new currency
        $currency = $repository->store(
            [
                'name'   => $value,
                'code'   => strtoupper(substr($value, 0, 3)),
                'symbol' => strtoupper(substr($value, 0, 1)),
            ]
        );
        $this->setCertainty(100);

        return $currency;

    }
}
