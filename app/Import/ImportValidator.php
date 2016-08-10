<?php
/**
 * ImportValidator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use Carbon\Carbon;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ImportValidator
 *
 * @package FireflyIII\Import
 */
class ImportValidator
{
    /** @var  Account */
    protected $defaultImportAccount;
    /** @var Collection */
    protected $entries;
    /** @var  User */
    protected $user;

    /**
     * ImportValidator constructor.
     *
     * @param Collection $entries
     */
    public function __construct(Collection $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Clean collection by filling in all the blanks.
     */
    public function clean()
    {
        /** @var ImportEntry $entry */
        foreach ($this->entries as $entry) {
            /*
             * X Adds the date (today) if no date is present.
             * X Determins the types of accounts involved (asset, expense, revenue).
             * X Determins the type of transaction (withdrawal, deposit, transfer).
             * - Determins the currency of the transaction.
             * X Adds a default description if there isn't one present.
             */
            $this->checkAmount($entry);
            $this->setDate($entry);
            $this->setAssetAccount($entry);
            $this->setOpposingAccount($entry);
            $this->cleanDescription($entry);
            $this->setTransactionType($entry);
            $this->setTransactionCurrency($entry);
        }


        /** @var ImportEntry $entry */
        foreach ($this->entries as $entry) {
            Log::debug('Description: ' . $entry->fields['description']);
        }

    }

    /**
     * @param Account $defaultImportAccount
     */
    public function setDefaultImportAccount(Account $defaultImportAccount)
    {
        $this->defaultImportAccount = $defaultImportAccount;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param ImportEntry $entry
     */
    private function checkAmount(ImportEntry $entry)
    {
        if ($entry->fields['amount'] == 0) {
            $entry->valid = false;
            Log::error('Amount of transaction is zero, cannot handle.');
        }
        Log::debug('Amount is OK.');
    }

    /**
     * @param ImportEntry $entry
     */
    private function cleanDescription(ImportEntry $entry)
    {
        if (!isset($entry->fields['description'])) {
            Log::debug('Set empty transaction description because field was not set.');
            $entry->fields['description'] = '(empty transaction description)';

            return;
        }
        if (is_null($entry->fields['description'])) {
            Log::debug('Set empty transaction description because field was null.');
            $entry->fields['description'] = '(empty transaction description)';

            return;
        }

        if (strlen($entry->fields['description']) == 0) {
            Log::debug('Set empty transaction description because field was empty.');
            $entry->fields['description'] = '(empty transaction description)';

            return;
        }
        Log::debug('Transaction description is OK.');
        $entry->fields['description'] = trim($entry->fields['description']);
    }

    /**
     * @param Account $account
     * @param string  $type
     *
     * @return Account
     */
    private function convertAccount(Account $account, string $type): Account
    {
        $accountType = $account->accountType->type;
        if ($accountType === $type) {
            return $account;
        }
        // find it first by new type:
        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);
        $result     = $repository->findByName($account->name, [$type]);
        if (is_null($result->id)) {
            // can convert account:
            $result = $repository->updateAccountType($account, $type);
        }

        return $result;


    }

    /**
     * @return Account
     */
    private function fallbackExpenseAccount(): Account
    {

        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);
        $name       = 'Unknown expense account';
        $result     = $repository->findByName($name, [AccountType::EXPENSE]);
        if (is_null($result->id)) {
            $result = $repository->store(
                ['name'   => $name, 'iban' => null, 'openingBalance' => 0, 'user' => $this->user->id, 'accountType' => 'expense', 'virtualBalance' => 0,
                 'active' => true]
            );
        }

        return $result;
    }

    /**
     * @return Account
     */
    private function fallbackRevenueAccount(): Account
    {

        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);
        $name       = 'Unknown revenue account';
        $result     = $repository->findByName($name, [AccountType::REVENUE]);
        if (is_null($result->id)) {
            $result = $repository->store(
                ['name'   => $name, 'iban' => null, 'openingBalance' => 0, 'user' => $this->user->id, 'accountType' => 'revenue', 'virtualBalance' => 0,
                 'active' => true]
            );
        }

        return $result;
    }

    /**
     * @param ImportEntry $entry
     */
    private function setAssetAccount(ImportEntry $entry)
    {
        if (is_null($entry->fields['asset-account'])) {
            if (!is_null($this->defaultImportAccount)) {
                Log::debug('Set asset account from default asset account');
                $entry->fields['asset-account'] = $this->defaultImportAccount;

                return;
            }
            // default import is null? should not happen. Entry cannot be imported.
            // set error message and block.
            $entry->valid = false;
            Log::error('Cannot import entry. Asset account is NULL and import account is also NULL.');
        }
        Log::debug('Asset account is OK.');
    }


    /**
     * @param ImportEntry $entry
     */
    private function setDate(ImportEntry $entry)
    {
        if (is_null($entry->fields['date-transaction'])) {
            // empty date field? find alternative.
            $alternatives = ['date-book', 'date-interest', 'date-process'];
            foreach ($alternatives as $alternative) {
                if (!is_null($entry->fields[$alternative])) {
                    Log::debug(sprintf('Copied date-transaction from %s.', $alternative));
                    $entry->fields['date-transaction'] = clone $entry->fields[$alternative];

                    return;
                }
            }
            // date is still null at this point
            Log::debug('Set date-transaction to today.');
            $entry->fields['date-transaction'] = new Carbon;

            return;
        }
        Log::debug('Date-transaction is OK');


    }

    /**
     * @param ImportEntry $entry
     */
    private function setOpposingAccount(ImportEntry $entry)
    {
        // empty opposing account. Create one based on amount.
        if (is_null($entry->fields['opposing-account'])) {

            if ($entry->fields['amount'] < 0) {
                // create or find general opposing expense account.
                Log::debug('Created fallback expense account');
                $entry->fields['opposing-account'] = $this->fallbackExpenseAccount();

                return;
            }
            Log::debug('Created fallback revenue account');
            $entry->fields['opposing-account'] = $this->fallbackRevenueAccount();

            return;
        }

        // opposing is of type "import". Convert to correct type (by amount):
        $type = $entry->fields['opposing-account']->accountType->type;
        if ($type == AccountType::IMPORT && $entry->fields['amount'] < 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::EXPENSE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted import account to expense account');

            return;
        }
        if ($type == AccountType::IMPORT && $entry->fields['amount'] > 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::REVENUE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted import account to revenue account');

            return;
        }
        // amount < 0, but opposing is revenue
        if ($type == AccountType::REVENUE && $entry->fields['amount'] < 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::EXPENSE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted revenue account to expense account');

            return;
        }

        // amount > 0, but opposing is expense
        if ($type == AccountType::EXPENSE && $entry->fields['amount'] < 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::REVENUE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted expense account to revenue account');

            return;
        }
        // account type is OK
        Log::debug('Opposing account is OK.');

    }

    /**
     * @param ImportEntry $entry
     */
    private function setTransactionCurrency(ImportEntry $entry)
    {
        if (is_null($entry->fields['currency'])) {
            /** @var CurrencyRepositoryInterface $repository */
            $repository                = app(CurrencyRepositoryInterface::class);
            $entry->fields['currency'] = $repository->findByCode(env('DEFAULT_CURRENCY', 'EUR'));
            Log::debug('Set currency to EUR');
            return;
        }
        Log::debug('Currency is OK');
    }

    /**
     * @param ImportEntry $entry
     */
    private function setTransactionType(ImportEntry $entry)
    {
        $type = $entry->fields['opposing-account']->accountType->type;
        switch ($type) {
            case AccountType::EXPENSE:
                $entry->fields['transaction-type'] = TransactionType::WITHDRAWAL;
                break;
            case AccountType::REVENUE:
                $entry->fields['transaction-type'] = TransactionType::DEPOSIT;
                break;
            case AccountType::ASSET:
                $entry->fields['transaction-type'] = TransactionType::TRANSFER;
                break;
        }
    }


}