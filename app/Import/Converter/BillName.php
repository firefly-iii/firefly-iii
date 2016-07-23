<?php
/**
 * BillName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @throws FireflyException
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using BillName', ['value' => $value]);

        if (strlen($value) === 0) {
            return new Bill;
        }

        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class, [$this->user]);

        if (isset($this->mapping[$value])) {
            Log::debug('Found bill in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $bill = $repository->find(intval($this->mapping[$value]));
            if (!is_null($bill->id)) {
                Log::debug('Found bill by ID', ['id' => $bill->id]);

                return $bill;
            }
        }

        // not mapped? Still try to find it first:
        $bill = $repository->findByName($value);
        if (!is_null($bill->id)) {
            Log::debug('Found bill by name ', ['id' => $bill->id]);

            return $bill;
        }

        // create new bill. Use a lot of made up values.
        $bill = $repository->store(
            [
                'name'        => $value,
                'match'       => $value,
                'amount_min'  => 1,
                'user_id'     => $this->user->id,
                'amount_max'  => 10,
                'date'        => date('Ymd'),
                'repeat_freq' => 'monthly',
                'skip'        => 0,
                'automatch'   => 0,
                'active'      => 1,

            ]
        );

        return $bill;


    }
}