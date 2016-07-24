<?php
/**
 * CurrencyId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;

/**
 * Class CurrencyId
 *
 * @package FireflyIII\Import\Converter
 */
class CurrencyId extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return TransactionCurrency
     */
    public function convert($value)
    {
        $value = intval(trim($value));
        Log::debug('Going to convert using CurrencyId', ['value' => $value]);

        if ($value === 0) {
            return new TransactionCurrency;
        }

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class, [$this->user]);

        if (isset($this->mapping[$value])) {
            Log::debug('Found currency in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $currency = $repository->find(intval($this->mapping[$value]));
            if (!is_null($currency->id)) {
                Log::debug('Found currency by ID', ['id' => $currency->id]);

                return $currency;
            }
        }

        // not mapped? Still try to find it first:
        $currency = $repository->find($value);
        if (!is_null($currency->id)) {
            Log::debug('Found currency by ID ', ['id' => $currency->id]);

            return $currency;
        }

        // should not really happen. If the ID does not match FF, what is FF supposed to do?
        return new TransactionCurrency;

    }
}