<?php
/**
 * ImportAccount.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class ImportAccount
 *
 * @package FireflyIII\Import\Object
 */
class ImportAccount
{

    /** @var  Account */
    private $account;
    /** @var array */
    private $accountIban = [];
    /** @var  array */
    private $accountId = [];
    /** @var array */
    private $accountName = [];
    /** @var array */
    private $accountNumber = [];
    /** @var  AccountRepositoryInterface */
    private $repository;
    /** @var  User */
    private $user;

    /**
     * ImportAccount constructor.
     */
    public function __construct()
    {
        $this->account    = new Account;
        $this->repository = app(AccountRepositoryInterface::class);
        Log::debug('Created ImportAccount.');

    }

    /**
     * @return bool
     */
    public function convertToExpense(): bool
    {
        if ($this->getAccount()->accountType->type === AccountType::EXPENSE) {
            return true;
        }
        // maybe that an account of expense account type already exists?
        $expenseType                    = AccountType::whereType(AccountType::EXPENSE)->first();
        $this->account->account_type_id = $expenseType->id;
        $this->account->save();

        return true;
    }

    public function convertToRevenue(): bool
    {
        if ($this->getAccount()->accountType->type === AccountType::REVENUE) {
            return true;
        }
        // maybe that an account of revenue account type already exists?
        $revenueType                    = AccountType::whereType(AccountType::REVENUE)->first();
        $this->account->account_type_id = $revenueType->id;
        $this->account->save();

        return true;
    }

    /**
     * @return Account
     */
    public function createAccount(): Account
    {
        if (!is_null($this->account->id)) {
            return $this->account;
        }
        Log::debug('In createAccount()');
        // check if any of them is mapped:
        $mapped = $this->findMappedObject();

        if (is_null($mapped->id)) {
            // none are, create new object!
            $data = [
                'accountType'    => 'import',
                'name'           => $this->accountName['value'] ?? '(no name)',
                'iban'           => $this->accountIban['value'] ?? null,
                'active'         => true,
                'virtualBalance' => null,
            ];
            if (!is_null($data['iban']) && $data['name'] === '(no name)') {
                $data['name'] = $data['iban'];
            }
            Log::debug('Search for maps resulted in nothing, create new one based on', $data);
            $account       = $this->repository->store($data);
            $this->account = $account;
            Log::info('Made new account.', ['input' => $data, 'new' => $account->toArray()]);


            return $account;
        }
        Log::debug('Mapped existing account.', ['new' => $mapped->toArray()]);
        $this->account = $mapped;

        return $mapped;
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param array $accountIban
     */
    public function setAccountIban(array $accountIban)
    {
        $this->accountIban = $accountIban;
    }

    /**
     * @param array $value
     */
    public function setAccountId(array $value)
    {
        $this->accountId = $value;
    }

    /**
     * @param array $accountName
     */
    public function setAccountName(array $accountName)
    {
        $this->accountName = $accountName;
    }

    /**
     * @param array $accountNumber
     */
    public function setAccountNumber(array $accountNumber)
    {
        $this->accountNumber = $accountNumber;
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
     * @return Account
     */
    private function findMappedObject(): Account
    {
        Log::debug('In findMappedObject()');
        $fields = ['accountId', 'accountIban', 'accountNumber', 'accountName'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped account based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (!is_null($mapped->id)) {
                Log::debug(sprintf('Found account #%d!', $mapped->id));

                return $mapped;
            }

        }
        Log::debug('Found no account on mapped data or no map present.');

        return new Account;
    }

    /**
     * @param array $array
     *
     * @return Account
     */
    private function getMappedObject(array $array): Account
    {
        Log::debug('In getMappedObject()');
        if (count($array) === 0) {
            Log::debug('Array is empty, nothing will come of this.');

            return new Account;
        }

        if (array_key_exists('mapped', $array) && is_null($array['mapped'])) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return new Account;
        }

        Log::debug('Finding a mapped object based on', $array);

        $search  = intval($array['mapped']);
        $account = $this->repository->find($search);

        Log::debug(sprintf('Found account! #%d ("%s"). Return it', $account->id, $account->name));

        return $account;
    }


}