<?php
/**
 * ImportBill.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ImportBill
 *
 * @package FireflyIII\Import\Object
 */
class ImportBill
{

    /** @var Bill */
    private $bill;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var BillRepositoryInterface */
    private $repository;
    /** @var  User */
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
        if (is_null($this->bill->id)) {
            $this->store();
        }

        return $this->bill;
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

        if (count($this->id) === 3) {
            Log::debug(sprintf('Finding bill with ID #%d', $this->id['value']));
            /** @var Bill $bill */
            $bill = $this->repository->find(intval($this->id['value']));
            if (!is_null($bill->id)) {
                Log::debug(sprintf('Found unmapped bill by ID (#%d): %s', $bill->id, $bill->name));

                return $bill;
            }
            Log::debug('Found nothing.');
        }
        // 2: find by name
        if (count($this->name) === 3) {
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

            if ($filtered->count() === 1) {
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
            if (!is_null($mapped->id)) {
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
        if (count($array) === 0) {
            Log::debug('Array is empty, nothing will come of this.');

            return new Bill;
        }

        if (array_key_exists('mapped', $array) && is_null($array['mapped'])) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new Bill;
        }

        Log::debug('Finding a mapped bill based on', $array);

        $search = intval($array['mapped']);
        $bill   = $this->repository->find($search);

        if (is_null($bill->id)) {
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
        if (!is_null($mapped->id)) {
            $this->bill = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (!is_null($found->id)) {
            $this->bill = $found;

            return true;
        }
        $name = $this->name['value'] ?? '';

        if (strlen($name) === 0) {
            return true;
        }

        Log::debug('Found no bill so must create one ourselves.');

        $data = [
            'name' => $name,
        ];

        $this->bill = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new bill #%d: %s', $this->bill->id, $this->bill->name));

        return true;
    }

}
