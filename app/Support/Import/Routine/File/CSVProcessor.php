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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
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


        $array = $this->parseImportables($importables);

        echo '<pre>';
        print_r($importables);
        print_r($lines);

        exit;
        die('here we are');
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob   = $job;
        $this->config      = $job->configuration;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);
        $this->repository->setUser($job->user);

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
            case 'foreign-currency-id':
            case 'foreign-currency-code':
                $newRole = 'foreign-currency-id';
                break;
            case 'bill-id':
            case 'bill-name':
                $newRole = 'bill-id';
                break;
            case 'currency-id':
            case 'currency-name':
            case 'currency-code':
            case 'currency-symbol':
                $newRole = 'currency-id';
                break;
            case 'budget-id':
            case 'budget-name':
                $newRole = 'budget-id';
                break;
            case 'category-id':
            case 'category-name':
                $newRole = 'category-id';
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
     * Each entry is an ImportTransaction that must be converted to an array compatible with the
     * journal factory. To do so some stuff must still be resolved. See below.
     *
     * @param array $importables
     *
     * @return array
     */
    private function parseImportables(array $importables): array
    {
        $array = [];
        /** @var ImportTransaction $importable */
        foreach ($importables as $importable) {

            // todo: verify bill mapping
            // todo: verify currency mapping.


            $entry = [
                'type'               => 'unknown', // todo
                'date'               => Carbon::createFromFormat($this->config['date-format'] ?? 'Ymd', $importable->getDate()),
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
                'bill_id'            => $importable->getBillId() === 0 ? null : $importable->getBillId(), //
                'bill_name'          => $importable->getBillId() !== 0 ? null : $importable->getBillName(),

                // transaction data:
                'transactions'       => [
                    [
                        'currency_id'           => null, // todo find ma
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => random_int(500, 5000) / 100,
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => null,
                        'source_name'           => 'Checking Account',
                        'destination_id'        => null,
                        'destination_name'      => 'Random expense account #' . random_int(1, 10000),
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];
        }

        return $array;
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
        $transaction = new ImportTransaction;
        // todo run all specifics on row.
        foreach ($line as $column => $value) {
            $value        = trim($value);
            $originalRole = $this->config['column-roles'][$column] ?? '_ignore';
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

                Log::debug(sprintf('Now at column #%d (%s), value "%s"', $column, $role, $value));
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
        foreach ($this->mappedValues as $role => $values) {
            $values = array_unique($values);
            if (count($values) > 0) {
                switch ($role) {
                    default:
                        throw new FireflyException(sprintf('Cannot validate mapped values for role "%s"', $role));
                    case 'opposing-id':
                    case 'account-id':
                        /** @var AccountRepositoryInterface $repository */
                        $repository = app(AccountRepositoryInterface::class);
                        $repository->setUser($this->importJob->user);
                        $set                       = $repository->getAccountsById($values);
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
}