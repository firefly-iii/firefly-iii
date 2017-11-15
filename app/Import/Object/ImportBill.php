<?php
/**
 * ImportBill.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Object;

use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Class ImportBill.
 */
class ImportBill
{
    /** @var string */
    private $amount = '1';
    /** @var Bill */
    private $bill;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var BillRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * ImportBill constructor.
     */
    public function __construct()
    {
        $this->bill       = new Bill;
        $this->repository = app(BillRepositoryInterface::class);
        Log::debug('Created ImportBill.');
    }

    /**
     * @return Bill
     */
    public function getBill(): Bill
    {
        if (null === $this->bill->id) {
            $this->store();
        }

        return $this->bill;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = Steam::positive($amount);
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $this->id = $id;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);
    }

    /**
     * @return Bill
     */
    private function findExistingObject(): Bill
    {
        Log::debug('In findExistingObject() for Bill');
        // 1: find by ID, or name

        if (3 === count($this->id)) {
            Log::debug(sprintf('Finding bill with ID #%d', $this->id['value']));
            /** @var Bill $bill */
            $bill = $this->repository->find(intval($this->id['value']));
            if (null !== $bill->id) {
                Log::debug(sprintf('Found unmapped bill by ID (#%d): %s', $bill->id, $bill->name));

                return $bill;
            }
            Log::debug('Found nothing.');
        }
        // 2: find by name
        if (3 === count($this->name)) {
            /** @var Collection $bills */
            $bills = $this->repository->getBills();
            $name  = $this->name['value'];
            Log::debug(sprintf('Finding bill with name %s', $name));
            $filtered = $bills->filter(
                function (Bill $bill) use ($name) {
                    if ($bill->name === $name) {
                        Log::debug(sprintf('Found unmapped bill by name (#%d): %s', $bill->id, $bill->name));

                        return $bill;
                    }

                    return null;
                }
            );

            if (1 === $filtered->count()) {
                return $filtered->first();
            }
            Log::debug('Found nothing.');
        }

        // 4: do not search by account number.
        Log::debug('Found NO existing bills.');

        return new Bill;
    }

    /**
     * @return Bill
     */
    private function findMappedObject(): Bill
    {
        Log::debug('In findMappedObject() for Bill');
        $fields = ['id', 'name'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped bill based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (null !== $mapped->id) {
                Log::debug(sprintf('Found bill #%d!', $mapped->id));

                return $mapped;
            }
        }
        Log::debug('Found no bill on mapped data or no map present.');

        return new Bill;
    }

    /**
     * @param array $array
     *
     * @return Bill
     */
    private function getMappedObject(array $array): Bill
    {
        Log::debug('In getMappedObject() for Bill');
        if (0 === count($array)) {
            Log::debug('Array is empty, nothing will come of this.');

            return new Bill;
        }

        if (array_key_exists('mapped', $array) && null === $array['mapped']) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new Bill;
        }

        Log::debug('Finding a mapped bill based on', $array);

        $search = intval($array['mapped']);
        $bill   = $this->repository->find($search);

        if (null === $bill->id) {
            Log::error(sprintf('There is no bill with id #%d. Invalid mapping will be ignored!', $search));

            return new Bill;
        }

        Log::debug(sprintf('Found bill! #%d ("%s"). Return it', $bill->id, $bill->name));

        return $bill;
    }

    /**
     * @return bool
     */
    private function store(): bool
    {
        // 1: find mapped object:
        $mapped = $this->findMappedObject();
        if (null !== $mapped->id) {
            $this->bill = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (null !== $found->id) {
            $this->bill = $found;

            return true;
        }
        $name = $this->name['value'] ?? '';

        if (0 === strlen($name)) {
            return true;
        }

        $data = [
            'name'        => $name,
            'match'       => $name,
            'amount_min'  => bcmul($this->amount, '0.9'),
            'amount_max'  => bcmul($this->amount, '1.1'),
            'user_id'     => $this->user->id,
            'date'        => date('Y-m-d'),
            'repeat_freq' => 'monthly',
            'skip'        => '0',
            'automatch'   => '0',
            'active'      => '1',
        ];

        Log::debug('Found no bill so must create one ourselves. Assume default values.', $data);

        $this->bill = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new bill #%d: %s', $this->bill->id, $this->bill->name));

        return true;
    }
}
