<?php
/**
 * BillName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Log;

/**
 * Class BillName
 *
 * @package FireflyIII\Import\Converter
 */
class BillName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Bill
     * @throws FireflyException
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using BillName', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);

            return new Bill;
        }

        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($this->user);

        if (isset($this->mapping[$value])) {
            Log::debug('Found bill in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $bill = $repository->find(intval($this->mapping[$value]));
            if (!is_null($bill->id)) {
                Log::debug('Found bill by ID', ['id' => $bill->id]);
                $this->setCertainty(100);

                return $bill;
            }
        }

        // not mapped? Still try to find it first:
        $bill = $repository->findByName($value);
        if (!is_null($bill->id)) {
            Log::debug('Found bill by name ', ['id' => $bill->id]);
            $this->setCertainty(100);

            return $bill;
        }

        // create new bill. Use a lot of made up values.
        $bill = $repository->store(
            [
                'name'        => $value,
                'match'       => $value,
                'amount_min'  => 1,
                'user'        => $this->user->id,
                'amount_max'  => 10,
                'date'        => date('Ymd'),
                'repeat_freq' => 'monthly',
                'skip'        => 0,
                'automatch'   => 0,
                'active'      => 1,

            ]
        );
        if (is_null($bill->id)) {
            $this->setCertainty(0);
            Log::info('Could not store new bill by name', $bill->getErrors()->toArray());

            return new Bill;
        }

        $this->setCertainty(100);

        return $bill;


    }
}
