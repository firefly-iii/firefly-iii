<?php
/**
 * CSVProcessor.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\File;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ColumnValue;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use Log;


/**
 * Class CSVProcessor
 *
 * @package FireflyIII\Support\Import\Routine\File
 */
class CSVProcessor implements FileProcessorInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var array */
    private $config;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var TransactionCurrency */
    private $defaultCurrency;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $mappedValues;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Fires the file processor.
     *
     * @return array
     * @throws FireflyException
     */
    public function run(): array
    {
        Log::debug('Now in CSVProcessor() run');

        // create separate objects to handle separate tasks:
        /** @var LineReader $lineReader */
        $lineReader = app(LineReader::class);
        $lineReader->setImportJob($this->importJob);
        $lines = $lineReader->getLines();

        // convert each line into a small set of "ColumnValue" objects,
        // joining each with its mapped counterpart.
        /** @var MappingConverger $mappingConverger */
        $mappingConverger = app(MappingConverger::class);
        $mappingConverger->setImportJob($this->importJob);
        $converged = $mappingConverger->converge($lines);

        // validate mapped values:
        /** @var MappedValuesValidator $validator */
        $validator    = app(MappedValuesValidator::class);
        $mappedValues = $validator->validate($mappingConverger->getMappedValues());

        // make import transaction things from these objects.
        /** @var ImportableCreator $creator */
        $creator     = app(ImportableCreator::class);
        $importables = $creator->convertSets($converged);

        // todo parse importables from $importables and $mappedValues


        // from here.
        // make import objects, according to their role:
        //$importables = $this->processLines($lines);

        // now validate all mapped values:
        //$this->validateMappedValues();

        //return $this->parseImportables($importables);
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        Log::debug('Now in setJob()');
        $this->importJob     = $job;
        $this->config        = $job->configuration;
        $this->repository    = app(ImportJobRepositoryInterface::class);
        $this->attachments   = app(AttachmentHelperInterface::class);
        $this->accountRepos  = app(AccountRepositoryInterface::class);
        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        $this->repository->setUser($job->user);
        $this->accountRepos->setUser($job->user);
        $this->currencyRepos->setUser($job->user);
        $this->defaultCurrency = app('amount')->getDefaultCurrencyByUser($job->user);

    }

    /**
     * If the value in the column is mapped to a certain ID,
     * the column where this ID must be placed will change.
     *
     * For example, if you map role "budget-name" with value "groceries" to 1,
     * then that should become the budget-id. Not the name.
     *
     * @param int $column
     * @param int $mapped
     *
     * @return string
     * @throws FireflyException
     */
    private function getRoleForColumn(int $column, int $mapped): string
    {
        $roles = $this->config['column-roles'];
        $role  = $roles[$column] ?? '_ignore';
        if ($mapped === 0) {
            Log::debug(sprintf('Column #%d with role "%s" is not mapped.', $column, $role));

            return $role;
        }
        if (!(isset($this->config['column-do-mapping'][$column]) && $this->config['column-do-mapping'][$column] === true)) {

            return $role;
        }
        switch ($role) {
            default:
                throw new FireflyException(sprintf('Cannot indicate new role for mapped role "%s"', $role));
            case 'account-id':
            case 'account-name':
            case 'account-iban':
            case 'account-number':
                $newRole = 'account-id';
                break;
            case 'bill-id':
            case 'bill-name':
                $newRole = 'bill-id';
                break;
            case 'budget-id':
            case 'budget-name':
                $newRole = 'budget-id';
                break;
            case 'currency-id':
            case 'currency-name':
            case 'currency-code':
            case 'currency-symbol':
                $newRole = 'currency-id';
                break;
            case 'category-id':
            case 'category-name':
                $newRole = 'category-id';
                break;
            case 'foreign-currency-id':
            case 'foreign-currency-code':
                $newRole = 'foreign-currency-id';
                break;
            case 'opposing-id':
            case 'opposing-name':
            case 'opposing-iban':
            case 'opposing-number':
                $newRole = 'opposing-id';
                break;
        }
        Log::debug(sprintf('Role was "%s", but because of mapping, role becomes "%s"', $role, $newRole));

        // also store the $mapped values in a "mappedValues" array.
        $this->mappedValues[$newRole][] = $mapped;

        return $newRole;
    }

    /**
     * Based upon data in the importable, try to find or create the asset account account.
     *
     * @param int|null $accountId
     * @param array    $accountData
     *
     * @return Account
     */
    private function mapAssetAccount(?int $accountId, array $accountData): Account
    {
        Log::debug('Now in mapAssetAccount()');
        if ((int)$accountId > 0) {
            // find asset account with this ID:
            $result = $this->accountRepos->findNull($accountId);
            if (null !== $result && $result->accountType->type === AccountType::ASSET) {
                Log::debug(sprintf('Found asset account "%s" based on given ID %d', $result->name, $accountId));

                return $result;
            }
            if (null !== $result && $result->accountType->type !== AccountType::ASSET) {
                Log::warning(
                    sprintf('Found account "%s" based on given ID %d but its a %s, return nothing.', $result->name, $accountId, $result->accountType->type)
                );
            }
        }
        // find by (respectively):
        // IBAN, accountNumber, name,
        $fields = ['iban' => 'findByIbanNull', 'number' => 'findByAccountNumber', 'name' => 'findByName'];
        foreach ($fields as $field => $function) {
            $value = $accountData[$field];
            if (null === $value) {
                continue;
            }
            $result = $this->accountRepos->$function($value, [AccountType::ASSET]);
            Log::debug(sprintf('Going to run %s() with argument "%s" (asset account)', $function, $value));
            if (null !== $result) {
                Log::debug(sprintf('Found asset account "%s". Return it!', $result->name));

                return $result;
            }
        }
        Log::debug('Found nothing. Will return default account.');
        // still NULL? Return default account.
        $default = null;
        if (isset($this->config['import-account'])) {
            $default = $this->accountRepos->findNull((int)$this->config['import-account']);
        }
        if (null === $default) {
            Log::debug('Default account is NULL! Simply result first account in system.');
            $default = $this->accountRepos->getAccountsByType([AccountType::ASSET])->first();
        }

        Log::debug(sprintf('Return default account "%s" (#%d). Return it!', $default->name, $default->id));

        return $default;
    }

    /**
     * @param int|null $currencyId
     * @param array    $currencyData
     *
     * @return TransactionCurrency|null
     */
    private function mapCurrency(?int $currencyId, array $currencyData): ?TransactionCurrency
    {
        if ((int)$currencyId > 0) {
            $result = $this->currencyRepos->findNull($currencyId);
            if (null !== $result) {
                Log::debug(sprintf('Found currency %s based on ID, return it.', $result->code));

                return $result;
            }
        }
        // try to find it by all other fields.
        $fields = ['code' => 'findByCodeNull', 'symbol' => 'findBySymbolNull', 'name' => 'findByNameNull'];
        foreach ($fields as $field => $function) {
            $value = $currencyData[$field];
            if ('' === (string)$value) {
                continue;
            }
            Log::debug(sprintf('Will search for currency using %s() and argument "%s".', $function, $value));
            $result = $this->currencyRepos->$function($value);
            if (null !== $result) {
                Log::debug(sprintf('Found result: Currency #%d, code "%s"', $result->id, $result->code));

                return $result;
            }
        }
        // if still nothing, and fields not null, try to create it
        $creation = [
            'code'           => $currencyData['code'],
            'name'           => $currencyData['name'],
            'symbol'         => $currencyData['symbol'],
            'decimal_places' => 2,
        ];

        // could be NULL
        return $this->currencyRepos->store($creation);
    }

    /**
     * @param int|null $accountId
     * @param string   $amount
     * @param array    $accountData
     *
     * @return Account
     */
    private function mapOpposingAccount(?int $accountId, string $amount, array $accountData): Account
    {
        // default assumption is we're looking for an expense account.
        $expectedType = AccountType::EXPENSE;
        $result       = null;
        Log::debug(sprintf('Going to search for accounts of type %s', $expectedType));
        if (bccomp($amount, '0') === 1) {
            // more than zero.
            $expectedType = AccountType::REVENUE;
            Log::debug(sprintf('Because amount is %s, will instead search for accounts of type %s', $amount, $expectedType));
        }

        Log::debug('Now in mapOpposingAccount()');
        if ((int)$accountId > 0) {
            // find any account with this ID:
            $result = $this->accountRepos->findNull($accountId);
            if (null !== $result && $result->accountType->type === $expectedType) {
                Log::debug(sprintf('Found account "%s" (%s) based on given ID %d. Return it!', $result->name, $result->accountType->type, $accountId));

                return $result;
            }
            if (null !== $result && $result->accountType->type !== $expectedType) {
                Log::warning(
                    sprintf(
                        'Found account "%s" (%s) based on given ID %d, but need a %s. Return nothing.', $result->name, $result->accountType->type, $accountId,
                        $expectedType
                    )
                );
            }
        }
        // if result is not null, system has found an account
        // but it's of the wrong type. If we dont have a name, use
        // the result's name, iban in the search below.
        if (null !== $result && '' === (string)$accountData['name']) {
            Log::debug(sprintf('Will search for account with name "%s" instead of NULL.', $result->name));
            $accountData['name'] = $result->name;
        }
        if (null !== $result && '' === $accountData['iban'] && '' !== (string)$result->iban) {
            Log::debug(sprintf('Will search for account with IBAN "%s" instead of NULL.', $result->iban));
            $accountData['iban'] = $result->iban;
        }


        // first search for $expectedType, then find asset:
        $searchTypes = [$expectedType, AccountType::ASSET];
        foreach ($searchTypes as $type) {
            // find by (respectively):
            // IBAN, accountNumber, name,
            $fields = ['iban' => 'findByIbanNull', 'number' => 'findByAccountNumber', 'name' => 'findByName'];
            foreach ($fields as $field => $function) {
                $value = $accountData[$field];
                if ('' === (string)$value) {
                    continue;
                }
                Log::debug(sprintf('Will search for account of type "%s" using %s() and argument "%s".', $type, $function, $value));
                $result = $this->accountRepos->$function($value, [$type]);
                if (null !== $result) {
                    Log::debug(sprintf('Found result: Account #%d, named "%s"', $result->id, $result->name));

                    return $result;
                }
            }
        }
        // not found? Create it!
        $creation = [
            'name'            => $accountData['name'] ?? '(no name)',
            'iban'            => $accountData['iban'],
            'accountNumber'   => $accountData['number'],
            'account_type_id' => null,
            'accountType'     => $expectedType,
            'active'          => true,
            'BIC'             => $accountData['bic'],
        ];
        Log::debug('Will try to store a new account: ', $creation);

        return $this->accountRepos->store($creation);
    }

    /**
     * Each entry is an ImportTransaction that must be converted to an array compatible with the
     * journal factory. To do so some stuff must still be resolved. See below.
     *
     * @param array $importables
     *
     * @return array
     * @throws FireflyException
     */
    private function parseImportables(array $importables): array
    {
        Log::debug('Now in parseImportables()');
        $array = [];
        $total = \count($importables);
        /** @var ImportTransaction $importable */
        foreach ($importables as $index => $importable) {
            Log::debug(sprintf('Now going to parse importable %d of %d', $index + 1, $total));
            $result = $this->parseSingleImportable($index, $importable);
            if (null !== $result) {
                $array[] = $result;
            }
        }

        return $array;
    }

    /**
     * @param int               $index
     * @param ImportTransaction $importable
     *
     * @return array
     * @throws FireflyException
     */
    private function parseSingleImportable(int $index, ImportTransaction $importable): ?array
    {

        $amount        = $importable->calculateAmount();
        $foreignAmount = $importable->calculateForeignAmount();
        if ('' === $amount) {
            $amount = $foreignAmount;
        }
        if ('' === $amount) {
            $this->repository->addErrorMessage($this->importJob, sprintf('No transaction amount information in row %d', $index + 1));

            return null;
        }


        /**
         * first finalise the amount. cehck debit en credit.
         * then get the accounts.
         * ->account always assumes were looking for an asset account.
         *   cannot create anything, will return the default account when nothing comes up.
         *
         * neg + account = assume asset account?
         * neg = assume withdrawal
         * pos = assume
         */

        $transactionType   = 'unknown';
        $accountId         = $this->verifyObjectId('account-id', $importable->getAccountId());
        $billId            = $this->verifyObjectId('bill-id', $importable->getForeignCurrencyId());
        $budgetId          = $this->verifyObjectId('budget-id', $importable->getBudgetId());
        $currencyId        = $this->verifyObjectId('currency-id', $importable->getCurrencyId());
        $categoryId        = $this->verifyObjectId('category-id', $importable->getCategoryId());
        $foreignCurrencyId = $this->verifyObjectId('foreign-currency-id', $importable->getForeignCurrencyId());
        $opposingId        = $this->verifyObjectId('opposing-id', $importable->getOpposingId());
        // also needs amount to be final.
        //$account           = $this->mapAccount($accountId, $importable->getAccountData());
        $source          = $this->mapAssetAccount($accountId, $importable->getAccountData());
        $destination     = $this->mapOpposingAccount($opposingId, $amount, $importable->getOpposingAccountData());
        $currency        = $this->mapCurrency($currencyId, $importable->getCurrencyData());
        $foreignCurrency = $this->mapCurrency($foreignCurrencyId, $importable->getForeignCurrencyData());
        if (null === $currency) {
            Log::debug(sprintf('Could not map currency, use default (%s)', $this->defaultCurrency->code));
            $currency = $this->defaultCurrency;
        }

        if (bccomp($amount, '0') === 1) {
            // amount is positive? Then switch:
            [$destination, $source] = [$source, $destination];
        }

        if ($source->accountType->type === AccountType::ASSET && $destination->accountType->type === AccountType::ASSET) {
            $transactionType = 'transfer';
        }
        if ($source->accountType->type === AccountType::REVENUE) {
            $transactionType = 'deposit';
        }
        if ($destination->accountType->type === AccountType::EXPENSE) {
            $transactionType = 'withdrawal';
        }
        if ($transactionType === 'unknown') {
            Log::error(
                sprintf(
                    'Cannot determine transaction type. Source account is a %s, destination is a %s',
                    $source->accountType->type, $destination->accountType->type
                ), ['source' => $source->toArray(), 'dest' => $destination->toArray()]
            );
        }

        try {
            $date = Carbon::createFromFormat($this->config['date-format'] ?? 'Ymd', $importable->getDate());
        } catch (InvalidDateException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $date = new Carbon;
        }

        $dateStr = $date->format('Y-m-d');

        return [
            'type'               => $transactionType,
            'date'               => $dateStr,
            'tags'               => $importable->getTags(), // todo make sure its filled.
            'user'               => $this->importJob->user_id,
            'notes'              => $importable->getNote(),

            // all custom fields:
            'internal_reference' => $importable->getMeta()['internal-reference'] ?? null,
            'sepa-cc'            => $importable->getMeta()['sepa-cc'] ?? null,
            'sepa-ct-op'         => $importable->getMeta()['sepa-ct-op'] ?? null,
            'sepa-ct-id'         => $importable->getMeta()['sepa-ct-id'] ?? null,
            'sepa-db'            => $importable->getMeta()['sepa-db'] ?? null,
            'sepa-country'       => $importable->getMeta()['sepa-countru'] ?? null,
            'sepa-ep'            => $importable->getMeta()['sepa-ep'] ?? null,
            'sepa-ci'            => $importable->getMeta()['sepa-ci'] ?? null,
            'interest_date'      => $importable->getMeta()['date-interest'] ?? null,
            'book_date'          => $importable->getMeta()['date-book'] ?? null,
            'process_date'       => $importable->getMeta()['date-process'] ?? null,
            'due_date'           => $importable->getMeta()['date-due'] ?? null,
            'payment_date'       => $importable->getMeta()['date-payment'] ?? null,
            'invoice_date'       => $importable->getMeta()['date-invoice'] ?? null,
            // todo external ID

            // journal data:
            'description'        => $importable->getDescription(),
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => $billId,
            'bill_name'          => null === $budgetId ? $importable->getBillName() : null,

            // transaction data:
            'transactions'       => [
                [
                    'currency_id'           => $currency->id,
                    'currency_code'         => null,
                    'description'           => $importable->getDescription(),
                    'amount'                => $amount,
                    'budget_id'             => $budgetId,
                    'budget_name'           => null === $budgetId ? $importable->getBudgetName() : null,
                    'category_id'           => $categoryId,
                    'category_name'         => null === $categoryId ? $importable->getCategoryName() : null,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'foreign_currency_id'   => $foreignCurrencyId,
                    'foreign_currency_code' => null,
                    'foreign_amount'        => $foreignAmount, // todo get me.
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];

    }


    /**
     * A small function that verifies if this particular key (ID) is present in the list
     * of valid keys.
     *
     * @param string $key
     * @param int    $objectId
     *
     * @return int|null
     */
    private function verifyObjectId(string $key, int $objectId): ?int
    {
        if (isset($this->mappedValues[$key]) && in_array($objectId, $this->mappedValues[$key])) {
            return $objectId;
        }

        return null;
    }
}
