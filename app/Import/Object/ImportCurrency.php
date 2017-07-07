<?php
/**
 * ImportCurrency.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Log;

class ImportCurrency
{
    /** @var array */
    private $code = [];
    /** @var  TransactionCurrency */
    private $currency;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var  CurrencyRepositoryInterface */
    private $repository;
    /** @var array */
    private $symbol = [];
    /** @var  User */
    private $user;

    /**
     * ImportCurrency constructor.
     */
    public function __construct()
    {
        $this->currency   = new TransactionCurrency;
        $this->repository = app(CurrencyRepositoryInterface::class);
    }

    /**
     * @return TransactionCurrency
     */
    public function getTransactionCurrency(): TransactionCurrency
    {
        if (!is_null($this->currency->id)) {
            return $this->currency;
        }
        Log::debug('In createCurrency()');
        // check if any of them is mapped:
        $mapped   = $this->findMappedObject();

        if (!is_null($mapped->id)) {

            Log::debug('Mapped existing currency.', ['new' => $mapped->toArray()]);
            $this->currency = $mapped;

            return $mapped;
        }

        $searched = $this->findExistingObject();
        if (!is_null($searched->id)) {
            Log::debug('Found existing currency.', ['found' => $searched->toArray()]);
            $this->currency = $searched;

            return $searched;
        }
        $data = [
            'code'           => $this->code['value'] ?? null,
            'symbol'         => $this->symbol['value'] ?? null,
            'name'           => $this->name['value'] ?? null,
            'decimal_places' => 2,
        ];
        if (is_null($data['code'])) {
            Log::debug('Need at least a code to create currency, return nothing.');

            return new TransactionCurrency();
        }

        Log::debug('Search for maps resulted in nothing, create new one based on', $data);
        $currency       = $this->repository->store($data);
        $this->currency = $currency;
        Log::info('Made new currency.', ['input' => $data, 'new' => $currency->toArray()]);


        return $currency;

    }

    /**
     * @param array $code
     */
    public function setCode(array $code)
    {
        $this->code = $code;
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $id['value'] = intval($id['value']);
        $this->id    = $id;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
    }

    /**
     * @param array $symbol
     */
    public function setSymbol(array $symbol)
    {
        $this->symbol = $symbol;
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
     * @return TransactionCurrency
     */
    private function findExistingObject(): TransactionCurrency
    {
        $search = [
            'id'     => 'find',
            'code'   => 'findByCode',
            'symbol' => 'findBySymbol',
            'name'   => 'findByName',
        ];
        foreach ($search as $field => $function) {
            $value = $this->$field['value'] ?? null;
            if (!is_null($value)) {
                Log::debug(sprintf('Searching for %s using function %s and value %s', $field, $function, $value));
                $currency = $this->repository->$function($value);

                if (!is_null($currency->id)) {
                    return $currency;
                }
            }
        }

        return new TransactionCurrency();
    }

    /**
     * @return TransactionCurrency
     */
    private function findMappedObject(): TransactionCurrency
    {
        Log::debug('In findMappedObject()');
        $fields = ['id', 'code', 'name', 'symbol'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped currency based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (!is_null($mapped->id)) {
                Log::debug(sprintf('Found currency #%d!', $mapped->id));

                return $mapped;
            }

        }
        Log::debug('Found no currency on mapped data or no map present.');

        return new TransactionCurrency;
    }

    /**
     * @param array $array
     *
     * @return TransactionCurrency
     */
    private function getMappedObject(array $array): TransactionCurrency
    {
        Log::debug('In getMappedObject()');
        if (count($array) === 0) {
            Log::debug('Array is empty, nothing will come of this.');

            return new TransactionCurrency;
        }

        if (array_key_exists('mapped', $array) && is_null($array['mapped'])) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new TransactionCurrency;
        }

        Log::debug('Finding a mapped object based on', $array);

        $search   = intval($array['mapped']);
        $currency = $this->repository->find($search);

        Log::debug(sprintf('Found currency! #%d ("%s"). Return it', $currency->id, $currency->name));

        return $currency;
    }


}