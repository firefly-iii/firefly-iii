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
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ColumnValue;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use Illuminate\Support\Collection;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
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

        // in order to actually map we also need to read the FULL file.
        try {
            $reader = $this->getReader();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        // get all lines from file:
        $lines = $this->getLines($reader);

        // make import objects, according to their role:
        $importables = $this->processLines($lines);

        // now validate all mapped values:
        $this->validateMappedValues();

        return $this->parseImportables($importables);
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        Log::debug('Now in setJob()');
        $this->importJob    = $job;
        $this->config       = $job->configuration;
        $this->repository   = app(ImportJobRepositoryInterface::class);
        $this->attachments  = app(AttachmentHelperInterface::class);
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->repository->setUser($job->user);
        $this->accountRepos->setUser($job->user);

    }

    /**
     * Returns all lines from the CSV file.
     *
     * @param Reader $reader
     *
     * @return array
     * @throws FireflyException
     */
    private function getLines(Reader $reader): array
    {
        Log::debug('now in getLines()');
        $offset = isset($this->config['has-headers']) && $this->config['has-headers'] === true ? 1 : 0;
        try {
            $stmt = (new Statement)->offset($offset);
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Could not execute statement: %s', $e->getMessage()));
        }
        $results = $stmt->process($reader);
        $lines   = [];
        foreach ($results as $line) {
            $lines[] = array_values($line);
        }

        return $lines;
    }

    /**
     * Return an instance of a CSV file reader so content of the file can be read.
     *
     * @throws \League\Csv\Exception
     */
    private function getReader(): Reader
    {
        Log::debug('Now in getReader()');
        $content = '';
        /** @var Collection $collection */
        $collection = $this->importJob->attachments;
        /** @var Attachment $attachment */
        foreach ($collection as $attachment) {
            if ($attachment->filename === 'import_file') {
                $content = $this->attachments->getAttachmentContent($attachment);
                break;
            }
        }
        $config = $this->repository->getConfiguration($this->importJob);
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);

        return $reader;
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
     * @param $importable
     *
     * @return Account
     */
    private function mapAssetAccount(?int $accountId, array $accountData): Account
    {
        Log::debug('Now in mapAssetAccount()');
        if ((int)$accountId > 0) {
            // find asset account with this ID:
            $result = $this->accountRepos->findNull($accountId);
            if (null !== $result) {
                Log::debug(sprintf('Found account "%s" based on given ID %d. Return it!', $result->name, $accountId));

                return $result;
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
                Log::debug(sprintf('Found asset account "%s". Return it!', $result->name, $accountId));

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
     * @param int|null $accountId
     * @param string   $amount
     * @param array    $accountData
     *
     * @return Account
     */
    private function mapOpposingAccount(?int $accountId, string $amount, array $accountData): Account
    {
        Log::debug('Now in mapOpposingAccount()');
        if ((int)$accountId > 0) {
            // find any account with this ID:
            $result = $this->accountRepos->findNull($accountId);
            if (null !== $result) {
                Log::debug(sprintf('Found account "%s" (%s) based on given ID %d. Return it!', $result->name, $result->accountType->type, $accountId));

                return $result;
            }
        }
        // default assumption is we're looking for an expense account.
        $expectedType = AccountType::EXPENSE;
        Log::debug(sprintf('Going to search for accounts of type %s', $expectedType));
        if (bccomp($amount, '0') === 1) {
            // more than zero.
            $expectedType = AccountType::REVENUE;
            Log::debug(sprintf('Because amount is %s, will instead search for accounts of type %s', $amount, $expectedType));
        }

        // first search for $expectedType, then find asset:
        $searchTypes = [$expectedType, AccountType::ASSET];
        foreach ($searchTypes as $type) {
            // find by (respectively):
            // IBAN, accountNumber, name,
            $fields = ['iban' => 'findByIbanNull', 'number' => 'findByAccountNumber', 'name' => 'findByName'];
            foreach ($fields as $field => $function) {
                $value = $accountData[$field];
                if (null === $value) {
                    continue;
                }
                Log::debug(sprintf('Will search for account of type "%s" using %s() and argument %s.', $type, $function, $value));
                $result = $this->accountRepos->$function($value, [$type]);
                if (null !== $result) {
                    Log::debug(sprintf('Found result: Account #%d, named "%s"', $result->id, $result->name));

                    return $result;
                }
            }
        }
        // not found? Create it!
        $creation = [
            'name'            => $accountData['name'],
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
        $total = count($importables);
        /** @var ImportTransaction $importable */
        foreach ($importables as $index => $importable) {
            Log::debug(sprintf('Now going to parse importable %d of %d', $index + 1, $total));
            $array[] = $this->parseSingleImportable($importable);
        }

        return $array;
    }

    /**
     * @param ImportTransaction $importable
     *
     * @return array
     * @throws FireflyException
     */
    private function parseSingleImportable(ImportTransaction $importable): array
    {

        $amount = $importable->calculateAmount();

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
        $currencyId        = $this->verifyObjectId('currency-id', $importable->getForeignCurrencyId());
        $categoryId        = $this->verifyObjectId('category-id', $importable->getCategoryId());
        $foreignCurrencyId = $this->verifyObjectId('foreign-currency-id', $importable->getForeignCurrencyId());
        $opposingId        = $this->verifyObjectId('opposing-id', $importable->getOpposingId());
        // also needs amount to be final.
        //$account           = $this->mapAccount($accountId, $importable->getAccountData());
        $source      = $this->mapAssetAccount($accountId, $importable->getAccountData());
        $destination = $this->mapOpposingAccount($opposingId, $amount, $importable->getOpposingAccountData());

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
                    'currency_id'           => $currencyId, // todo what if null?
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
                    'foreign_amount'        => null, // todo get me.
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];

    }

    /**
     * Process all lines in the CSV file. Each line is processed separately.
     *
     * @param array $lines
     *
     * @return array
     * @throws FireflyException
     */
    private function processLines(array $lines): array
    {
        Log::debug('Now in processLines()');
        $processed = [];
        $count     = \count($lines);
        foreach ($lines as $index => $line) {
            Log::debug(sprintf('Now at line #%d of #%d', $index, $count));
            $processed[] = $this->processSingleLine($line);
        }

        return $processed;
    }

    /**
     * Process a single line in the CSV file.
     * Each column is processed separately.
     *
     * @param array $line
     * @param array $roles
     *
     * @return ImportTransaction
     * @throws FireflyException
     */
    private function processSingleLine(array $line): ImportTransaction
    {
        Log::debug('Now in processSingleLine()');
        $transaction = new ImportTransaction;
        // todo run all specifics on row.
        foreach ($line as $column => $value) {

            $value        = trim($value);
            $originalRole = $this->config['column-roles'][$column] ?? '_ignore';
            Log::debug(sprintf('Now at column #%d (%s), value "%s"', $column, $originalRole, $value));
            if (\strlen($value) > 0 && $originalRole !== '_ignore') {

                // is a mapped value present?
                $mapped = $this->config['column-mapping-config'][$column][$value] ?? 0;
                // the role might change.
                $role = $this->getRoleForColumn($column, $mapped);

                $columnValue = new ColumnValue;
                $columnValue->setValue($value);
                $columnValue->setRole($role);
                $columnValue->setMappedValue($mapped);
                $columnValue->setOriginalRole($originalRole);
                $transaction->addColumnValue($columnValue);
            }
            if ('' === $value) {
                Log::debug('Column skipped because value is empty.');
            }
        }

        return $transaction;
    }

    /**
     * For each value that has been mapped, this method will check if the mapped value(s) are actually existing
     *
     * User may indicate that he wants his categories mapped to category #3, #4, #5 but if #5 is owned by another
     * user it will be removed.
     *
     * @throws FireflyException
     */
    private function validateMappedValues()
    {
        Log::debug('Now in validateMappedValues()');
        foreach ($this->mappedValues as $role => $values) {
            $values = array_unique($values);
            if (count($values) > 0) {
                switch ($role) {
                    default:
                        throw new FireflyException(sprintf('Cannot validate mapped values for role "%s"', $role));
                    case 'opposing-id':
                    case 'account-id':
                        $set                       = $this->accountRepos->getAccountsById($values);
                        $valid                     = $set->pluck('id')->toArray();
                        $this->mappedValues[$role] = $valid;
                        break;
                    case 'currency-id':
                    case 'foreign-currency-id':
                        /** @var CurrencyRepositoryInterface $repository */
                        $repository = app(CurrencyRepositoryInterface::class);
                        $repository->setUser($this->importJob->user);
                        $set                       = $repository->getByIds($values);
                        $valid                     = $set->pluck('id')->toArray();
                        $this->mappedValues[$role] = $valid;
                        break;
                    case 'bill-id':
                        /** @var BillRepositoryInterface $repository */
                        $repository = app(BillRepositoryInterface::class);
                        $repository->setUser($this->importJob->user);
                        $set                       = $repository->getByIds($values);
                        $valid                     = $set->pluck('id')->toArray();
                        $this->mappedValues[$role] = $valid;
                        break;
                    case 'budget-id':
                        /** @var BudgetRepositoryInterface $repository */
                        $repository = app(BudgetRepositoryInterface::class);
                        $repository->setUser($this->importJob->user);
                        $set                       = $repository->getByIds($values);
                        $valid                     = $set->pluck('id')->toArray();
                        $this->mappedValues[$role] = $valid;
                        break;
                    case 'category-id':
                        /** @var CategoryRepositoryInterface $repository */
                        $repository = app(CategoryRepositoryInterface::class);
                        $repository->setUser($this->importJob->user);
                        $set                       = $repository->getByIds($values);
                        $valid                     = $set->pluck('id')->toArray();
                        $this->mappedValues[$role] = $valid;
                        break;
                }
            }
        }
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