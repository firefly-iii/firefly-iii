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
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class ImportValidator
 *
 * @package FireflyIII\Import
 */
class ImportValidator
{
    /** @var  ImportJob */
    public $job;
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
    public function clean(): Collection
    {
        Log::notice(sprintf('Started validating %d entry(ies).', $this->entries->count()));
        $newCollection = new Collection;
        /** @var ImportEntry $entry */
        foreach ($this->entries as $index => $entry) {
            Log::debug(sprintf('--- import validator start for row %d ---', $index));
            /*
             * X Adds the date (today) if no date is present.
             * X Determins the types of accounts involved (asset, expense, revenue).
             * X Determins the type of transaction (withdrawal, deposit, transfer).
             * - Determins the currency of the transaction.
             * X Adds a default description if there isn't one present.
             */
            $entry = $this->checkAmount($entry);
            $entry = $this->setDate($entry);
            $entry = $this->setAssetAccount($entry);
            $entry = $this->setOpposingAccount($entry);
            $entry = $this->cleanDescription($entry);
            $entry = $this->setTransactionType($entry);
            $entry = $this->setTransactionCurrency($entry);

            $newCollection->put($index, $entry);
            $this->job->addStepsDone(1);
        }
        Log::notice(sprintf('Finished validating %d entry(ies).', $newCollection->count()));

        return $newCollection;
    }

    /**
     * @param Account $defaultImportAccount
     */
    public function setDefaultImportAccount(Account $defaultImportAccount)
    {
        $this->defaultImportAccount = $defaultImportAccount;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
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
     *
     * @return ImportEntry
     */
    private function checkAmount(ImportEntry $entry): ImportEntry
    {
        if ($entry->fields['amount'] == 0) {
            $entry->valid = false;
            $entry->errors->push('Amount of transaction is zero, cannot handle.');
            Log::warning('Amount of transaction is zero, cannot handle.');

            return $entry;
        }
        Log::debug('Amount is OK.');

        return $entry;
    }

    /**
     * @param ImportEntry $entry
     *
     * @return ImportEntry
     */
    private function cleanDescription(ImportEntry $entry): ImportEntry
    {

        if (!isset($entry->fields['description'])) {
            Log::debug('Set empty transaction description because field was not set.');
            $entry->fields['description'] = '(empty transaction description)';

            return $entry;
        }
        if (is_null($entry->fields['description'])) {
            Log::debug('Set empty transaction description because field was null.');
            $entry->fields['description'] = '(empty transaction description)';

            return $entry;
        }
        $entry->fields['description'] = trim($entry->fields['description']);

        if (strlen($entry->fields['description']) == 0) {
            Log::debug('Set empty transaction description because field was empty.');
            $entry->fields['description'] = '(empty transaction description)';

            return $entry;
        }
        Log::debug('Transaction description is OK.', ['description' => $entry->fields['description']]);

        return $entry;
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
            Log::debug(sprintf('Account %s already of type %s', $account->name, $type));

            return $account;
        }
        // find it first by new type:
        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);
        $result     = $repository->findByName($account->name, [$type]);
        if (is_null($result->id)) {
            // can convert account:
            Log::debug(sprintf('No account named %s of type %s, will convert.', $account->name, $type));
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
     *
     * @return ImportEntry
     */
    private function setAssetAccount(ImportEntry $entry): ImportEntry
    {
        if (is_null($entry->fields['asset-account'])) {
            if (!is_null($this->defaultImportAccount)) {
                Log::debug('Set asset account from default asset account');
                $entry->fields['asset-account'] = $this->defaultImportAccount;

                return $entry;
            }
            // default import is null? should not happen. Entry cannot be imported.
            // set error message and block.
            $entry->valid = false;
            Log::warning('Cannot import entry. Asset account is NULL and import account is also NULL.');

        }
        Log::debug('Asset account is OK.', ['id' => $entry->fields['asset-account']->id, 'name' => $entry->fields['asset-account']->name]);

        return $entry;
    }


    /**
     * @param ImportEntry $entry
     *
     * @return ImportEntry
     */
    private function setDate(ImportEntry $entry): ImportEntry
    {
        if (is_null($entry->fields['date-transaction']) || $entry->certain['date-transaction'] == 0) {
            // empty date field? find alternative.
            $alternatives = ['date-book', 'date-interest', 'date-process'];
            foreach ($alternatives as $alternative) {
                if (!is_null($entry->fields[$alternative])) {
                    Log::debug(sprintf('Copied date-transaction from %s.', $alternative));
                    $entry->fields['date-transaction'] = clone $entry->fields[$alternative];

                    return $entry;
                }
            }
            // date is still null at this point
            Log::debug('Set date-transaction to today.');
            $entry->fields['date-transaction'] = new Carbon;

            return $entry;
        }

        // confidence is zero?

        Log::debug('Date-transaction is OK');

        return $entry;
    }

    /**
     * @param ImportEntry $entry
     *
     * @return ImportEntry
     */
    private function setOpposingAccount(ImportEntry $entry): ImportEntry
    {
        // empty opposing account. Create one based on amount.
        if (is_null($entry->fields['opposing-account'])) {

            if ($entry->fields['amount'] < 0) {
                // create or find general opposing expense account.
                Log::debug('Created fallback expense account');
                $entry->fields['opposing-account'] = $this->fallbackExpenseAccount();

                return $entry;
            }
            Log::debug('Created fallback revenue account');
            $entry->fields['opposing-account'] = $this->fallbackRevenueAccount();

            return $entry;
        }

        // opposing is of type "import". Convert to correct type (by amount):
        $type = $entry->fields['opposing-account']->accountType->type;
        if ($type == AccountType::IMPORT && $entry->fields['amount'] < 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::EXPENSE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted import account to expense account');

            return $entry;
        }
        if ($type == AccountType::IMPORT && $entry->fields['amount'] > 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::REVENUE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted import account to revenue account');

            return $entry;
        }
        // amount < 0, but opposing is revenue
        if ($type == AccountType::REVENUE && $entry->fields['amount'] < 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::EXPENSE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted revenue account to expense account');

            return $entry;
        }

        // amount > 0, but opposing is expense
        if ($type == AccountType::EXPENSE && $entry->fields['amount'] > 0) {
            $account                           = $this->convertAccount($entry->fields['opposing-account'], AccountType::REVENUE);
            $entry->fields['opposing-account'] = $account;
            Log::debug('Converted expense account to revenue account');

            return $entry;
        }
        // account type is OK
        Log::debug('Opposing account is OK.');

        return $entry;

    }

    /**
     * @param ImportEntry $entry
     *
     * @return ImportEntry
     */
    private function setTransactionCurrency(ImportEntry $entry): ImportEntry
    {
        if (is_null($entry->fields['currency'])) {
            /** @var CurrencyRepositoryInterface $repository */
            $repository = app(CurrencyRepositoryInterface::class, [$this->user]);
            // is the default currency for the user or the system
            $defaultCode = Preferences::getForUser($this->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;

            $entry->fields['currency'] = $repository->findByCode($defaultCode);
            Log::debug(sprintf('Set currency to %s', $defaultCode));

            return $entry;
        }
        Log::debug(sprintf('Currency is OK: %s', $entry->fields['currency']->code));

        return $entry;
    }

    /**
     * @param ImportEntry $entry
     *
     * @return ImportEntry
     */
    private function setTransactionType(ImportEntry $entry): ImportEntry
    {
        Log::debug(sprintf('Opposing account is of type %s', $entry->fields['opposing-account']->accountType->type));
        $type = $entry->fields['opposing-account']->accountType->type;
        switch ($type) {
            case AccountType::EXPENSE:
                $entry->fields['transaction-type'] = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
                Log::debug('Transaction type is now withdrawal.');

                return $entry;
            case AccountType::REVENUE:
                $entry->fields['transaction-type'] = TransactionType::whereType(TransactionType::DEPOSIT)->first();
                Log::debug('Transaction type is now deposit.');

                return $entry;
            case AccountType::ASSET:
                $entry->fields['transaction-type'] = TransactionType::whereType(TransactionType::TRANSFER)->first();
                Log::debug('Transaction type is now transfer.');

                return $entry;
        }
        Log::warning(sprintf('Opposing account is of type %s, cannot handle this.', $type));
        $entry->valid = false;
        $entry->errors->push(sprintf('Opposing account is of type %s, cannot handle this.', $type));

        return $entry;
    }


}
