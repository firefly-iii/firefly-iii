<?php

/**
 * TransactionJournalFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Factory;

use Exception;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    use JournalServiceTrait;

    private AccountRepositoryInterface         $accountRepository;
    private AccountValidator                   $accountValidator;
    private BillRepositoryInterface            $billRepository;
    private CurrencyRepositoryInterface        $currencyRepository;
    private bool                               $errorOnHash;
    private array                              $fields;
    private PiggyBankEventFactory              $piggyEventFactory;
    private PiggyBankRepositoryInterface       $piggyRepository;
    private TransactionTypeRepositoryInterface $typeRepository;
    private User                               $user;

    /**
     * Constructor.
     *
     * @throws Exception
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->errorOnHash        = false;
        $this->fields             = config('firefly.journal_meta_fields');
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->typeRepository     = app(TransactionTypeRepositoryInterface::class);
        $this->billRepository     = app(BillRepositoryInterface::class);
        $this->budgetRepository   = app(BudgetRepositoryInterface::class);
        $this->categoryRepository = app(CategoryRepositoryInterface::class);
        $this->piggyRepository    = app(PiggyBankRepositoryInterface::class);
        $this->piggyEventFactory  = app(PiggyBankEventFactory::class);
        $this->tagFactory         = app(TagFactory::class);
        $this->accountValidator   = app(AccountValidator::class);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
    }

    /**
     * Store a new (set of) transaction journals.
     *
     * @param array $data
     *
     * @return Collection
     * @throws FireflyException
     * @throws DuplicateTransactionException
     */
    public function create(array $data): Collection
    {
        Log::debug('Now in TransactionJournalFactory::create()');
        // convert to special object.
        $dataObject = new NullArrayObject($data);

        Log::debug('Start of TransactionJournalFactory::create()');
        $collection   = new Collection;
        $transactions = $dataObject['transactions'] ?? [];
        if (0 === count($transactions)) {
            Log::error('There are no transactions in the array, the TransactionJournalFactory cannot continue.');

            return new Collection;
        }
        try {
            /** @var array $row */
            foreach ($transactions as $index => $row) {
                Log::debug(sprintf('Now creating journal %d/%d', $index + 1, count($transactions)));
                $journal = $this->createJournal(new NullArrayObject($row));
                if (null !== $journal) {
                    $collection->push($journal);
                }
                if (null === $journal) {
                    Log::error('The createJournal() method returned NULL. This may indicate an error.');
                }
            }
        } catch (DuplicateTransactionException $e) {
            Log::warning('TransactionJournalFactory::create() caught a duplicate journal in createJournal()');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->forceDeleteOnError($collection);
            throw new DuplicateTransactionException($e->getMessage(), 0, $e);
        } catch (FireflyException $e) {
            Log::warning('TransactionJournalFactory::create() caught an exception.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->forceDeleteOnError($collection);
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $collection;
    }

    /**
     * @param NullArrayObject $row
     *
     * @return TransactionJournal|null
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function createJournal(NullArrayObject $row): ?TransactionJournal
    {
        $row['import_hash_v2'] = $this->hashArray($row);

        $this->errorIfDuplicate($row['import_hash_v2']);

        /** Some basic fields */
        $type            = $this->typeRepository->findTransactionType(null, $row['type']);
        $carbon          = $row['date'] ?? today(config('app.timezone'));
        $order           = $row['order'] ?? 0;
        $currency        = $this->currencyRepository->findCurrency((int)$row['currency_id'], $row['currency_code']);
        $foreignCurrency = $this->currencyRepository->findCurrencyNull($row['foreign_currency_id'], $row['foreign_currency_code']);
        $bill            = $this->billRepository->findBill((int)$row['bill_id'], $row['bill_name']);
        $billId          = TransactionType::WITHDRAWAL === $type->type && null !== $bill ? $bill->id : null;
        $description     = (string)$row['description'];

        /** Manipulate basic fields */
        $carbon->setTimezone(config('app.timezone'));

        try {
            // validate source and destination using a new Validator.
            $this->validateAccounts($row);
        } catch (FireflyException $e) {
            Log::error('Could not validate source or destination.');
            Log::error($e->getMessage());

            return null;
        }

        // typeOverrule: the account validator may have another opinion on the transaction type.
        // not sure what to do with this.

        /** create or get source and destination accounts  */
        $sourceInfo = [
            'id'          => $row['source_id'],
            'name'        => $row['source_name'],
            'iban'        => $row['source_iban'],
            'number'      => $row['source_number'],
            'bic'         => $row['source_bic'],
            'currency_id' => $currency->id,
        ];

        $destInfo = [
            'id'          => $row['destination_id'],
            'name'        => $row['destination_name'],
            'iban'        => $row['destination_iban'],
            'number'      => $row['destination_number'],
            'bic'         => $row['destination_bic'],
            'currency_id' => $currency->id,
        ];
        Log::debug('Source info:', $sourceInfo);
        Log::debug('Destination info:', $destInfo);
        Log::debug('Now calling getAccount for the source.');
        $sourceAccount = $this->getAccount($type->type, 'source', $sourceInfo);
        Log::debug('Now calling getAccount for the destination.');
        $destinationAccount = $this->getAccount($type->type, 'destination', $destInfo);
        Log::debug('Done with getAccount(2x)');
        $currency        = $this->getCurrencyByAccount($type->type, $currency, $sourceAccount, $destinationAccount);
        $foreignCurrency = $this->compareCurrencies($currency, $foreignCurrency);
        $foreignCurrency = $this->getForeignByAccount($type->type, $foreignCurrency, $destinationAccount);
        $description     = $this->getDescription($description);

        /** Create a basic journal. */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $type->id,
                'bill_id'                 => $billId,
                'transaction_currency_id' => $currency->id,
                'description'             => substr($description, 0, 1000),
                'date'                    => $carbon->format('Y-m-d H:i:s'),
                'order'                   => $order,
                'tag_count'               => 0,
                'completed'               => 0,
            ]
        );
        Log::debug(sprintf('Created new journal #%d: "%s"', $journal->id, $journal->description));

        /** Create two transactions. */
        $transactionFactory = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($sourceAccount);
        $transactionFactory->setCurrency($currency);
        $transactionFactory->setForeignCurrency($foreignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);
        try {
            $negative = $transactionFactory->createNegative((string)$row['amount'], (string)$row['foreign_amount']);
        } catch (FireflyException $e) {
            Log::error('Exception creating negative transaction.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->forceDeleteOnError(new Collection([$journal]));
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        // and the destination one:
        /** @var TransactionFactory $transactionFactory */
        $transactionFactory = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($destinationAccount);
        $transactionFactory->setCurrency($currency);
        $transactionFactory->setForeignCurrency($foreignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);
        try {
            $transactionFactory->createPositive((string)$row['amount'], (string)$row['foreign_amount']);
        } catch (FireflyException $e) {
            Log::error('Exception creating positive transaction.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            Log::warning('Delete negative transaction.');
            $this->forceTrDelete($negative);
            $this->forceDeleteOnError(new Collection([$journal]));
            throw new FireflyException($e->getMessage(), 0, $e);
        }
        // verify that journal has two transactions. Otherwise, delete and cancel.
        $journal->completed = true;
        $journal->save();

        /** Link all other data to the journal. */

        /** Link budget */
        $this->storeBudget($journal, $row);

        /** Link category */
        $this->storeCategory($journal, $row);

        /** Set notes */
        $this->storeNotes($journal, $row['notes']);

        /** Set piggy bank */
        $this->storePiggyEvent($journal, $row);

        /** Set tags */
        $this->storeTags($journal, $row['tags']);

        /** set all meta fields */
        $this->storeMetaFields($journal, $row);

        return $journal;
    }

    /**
     * @param NullArrayObject $row
     *
     * @return string
     */
    private function hashArray(NullArrayObject $row): string
    {
        $dataRow = $row->getArrayCopy();

        unset($dataRow['import_hash_v2'], $dataRow['original_source']);
        $json = json_encode($dataRow, JSON_THROW_ON_ERROR, 512);
        if (false === $json) {

            $json = json_encode((string)microtime(), JSON_THROW_ON_ERROR, 512);
            Log::error(sprintf('Could not hash the original row! %s', json_last_error_msg()), $dataRow);

        }
        $hash = hash('sha256', $json);
        Log::debug(sprintf('The hash is: %s', $hash), $dataRow);

        return $hash;
    }

    /**
     * If this transaction already exists, throw an error.
     *
     * @param string $hash
     *
     * @throws DuplicateTransactionException
     */
    private function errorIfDuplicate(string $hash): void
    {
        Log::debug(sprintf('In errorIfDuplicate(%s)', $hash));
        if (false === $this->errorOnHash) {
            return;
        }
        $result = null;
        if ($this->errorOnHash) {
            Log::debug('Will verify duplicate!');
            /** @var TransactionJournalMeta $result */
            $result = TransactionJournalMeta::where('data', json_encode($hash, JSON_THROW_ON_ERROR))
                                            ->with(['transactionJournal', 'transactionJournal.transactionGroup'])
                                            ->first();
        }
        if (null !== $result) {
            Log::warning(sprintf('Found a duplicate in errorIfDuplicate because hash %s is not unique!', $hash));
            throw new DuplicateTransactionException(sprintf('Duplicate of transaction #%d.', $result->transactionJournal->transaction_group_id));
        }
    }

    /**
     * @param NullArrayObject $data
     *
     * @throws FireflyException
     */
    private function validateAccounts(NullArrayObject $data): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $transactionType = $data['type'] ?? 'invalid';
        $this->accountValidator->setUser($this->user);
        $this->accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId    = $data['source_id'] ? (int)$data['source_id'] : null;
        $sourceName  = $data['source_name'] ? (string)$data['source_name'] : null;
        $validSource = $this->accountValidator->validateSource($sourceId, $sourceName, null);

        // do something with result:
        if (false === $validSource) {
            throw new FireflyException(sprintf('Source: %s', $this->accountValidator->sourceError));
        }
        Log::debug('Source seems valid.');
        // validate destination account
        $destinationId    = $data['destination_id'] ? (int)$data['destination_id'] : null;
        $destinationName  = $data['destination_name'] ? (string)$data['destination_name'] : null;
        $validDestination = $this->accountValidator->validateDestination($destinationId, $destinationName, null);
        // do something with result:
        if (false === $validDestination) {
            throw new FireflyException(sprintf('Destination: %s', $this->accountValidator->destError));
        }
    }

    /**
     * @param string                   $type
     * @param TransactionCurrency|null $currency
     * @param Account                  $source
     * @param Account                  $destination
     *
     * @return TransactionCurrency
     */
    private function getCurrencyByAccount(string $type, ?TransactionCurrency $currency, Account $source, Account $destination): TransactionCurrency
    {
        Log::debug('Now ingetCurrencyByAccount()');
        switch ($type) {
            default:
            case TransactionType::WITHDRAWAL:
            case TransactionType::TRANSFER:
                return $this->getCurrency($currency, $source);
            case TransactionType::DEPOSIT:
                return $this->getCurrency($currency, $destination);

        }
    }

    /**
     * @param TransactionCurrency|null $currency
     * @param Account                  $account
     *
     * @return TransactionCurrency
     */
    private function getCurrency(?TransactionCurrency $currency, Account $account): TransactionCurrency
    {
        Log::debug('Now in getCurrency()');
        $preference = $this->accountRepository->getAccountCurrency($account);
        if (null === $preference && null === $currency) {
            // return user's default:
            return app('amount')->getDefaultCurrencyByUser($this->user);
        }
        $result = ($preference ?? $currency) ?? app('amount')->getSystemCurrency();
        Log::debug(sprintf('Currency is now #%d (%s) because of account #%d (%s)', $result->id, $result->code, $account->id, $account->name));

        return $result;
    }

    /**
     * Set foreign currency to NULL if it's the same as the normal currency:
     *
     * @param TransactionCurrency      $currency
     * @param TransactionCurrency|null $foreignCurrency
     *
     * @return TransactionCurrency|null
     */
    private function compareCurrencies(?TransactionCurrency $currency, ?TransactionCurrency $foreignCurrency): ?TransactionCurrency
    {
        if (null === $currency) {
            return null;
        }
        if (null !== $foreignCurrency && $foreignCurrency->id === $currency->id) {
            return null;
        }

        return $foreignCurrency;
    }

    /**
     * @param string                   $type
     * @param TransactionCurrency|null $foreignCurrency
     * @param Account                  $destination
     *
     * @return TransactionCurrency|null
     */
    private function getForeignByAccount(string $type, ?TransactionCurrency $foreignCurrency, Account $destination): ?TransactionCurrency
    {
        if (TransactionType::TRANSFER === $type) {
            return $this->getCurrency($foreignCurrency, $destination);
        }

        return $foreignCurrency;
    }

    /**
     * @param string $description
     *
     * @return string
     */
    private function getDescription(string $description): string
    {
        $description = '' === $description ? '(empty description)' : $description;

        return substr($description, 0, 255);
    }

    /**
     * Force the deletion of an entire set of transaction journals and their meta object in case of
     * an error creating a group.
     *
     * @param Collection $collection
     */
    private function forceDeleteOnError(Collection $collection): void
    {
        Log::debug(sprintf('forceDeleteOnError on collection size %d item(s)', $collection->count()));
        $service = app(JournalDestroyService::class);
        /** @var TransactionJournal $journal */
        foreach ($collection as $journal) {
            Log::debug(sprintf('forceDeleteOnError on journal #%d', $journal->id));
            $service->destroy($journal);
        }
    }

    /**
     * @param Transaction $transaction
     */
    private function forceTrDelete(Transaction $transaction): void
    {
        try {
            $transaction->delete();
        } catch (Exception $e) { // @phpstan-ignore-line
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            Log::error('Could not delete negative transaction.');
        }
    }

    /**
     * Link a piggy bank to this journal.
     *
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     */
    private function storePiggyEvent(TransactionJournal $journal, NullArrayObject $data): void
    {
        Log::debug('Will now store piggy event.');
        if (!$journal->isTransfer()) {
            Log::debug('Journal is not a transfer, do nothing.');

            return;
        }

        $piggyBank = $this->piggyRepository->findPiggyBank((int)$data['piggy_bank_id'], $data['piggy_bank_name']);

        if (null !== $piggyBank) {
            $this->piggyEventFactory->create($journal, $piggyBank);
            Log::debug('Create piggy event.');

            return;
        }
        Log::debug('Create no piggy event');
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $transaction
     */
    private function storeMetaFields(TransactionJournal $journal, NullArrayObject $transaction): void
    {
        foreach ($this->fields as $field) {
            $this->storeMeta($journal, $transaction, $field);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     * @param string             $field
     */
    protected function storeMeta(TransactionJournal $journal, NullArrayObject $data, string $field): void
    {
        $set = [
            'journal' => $journal,
            'name'    => $field,
            'data'    => (string)($data[$field] ?? ''),
        ];

        Log::debug(sprintf('Going to store meta-field "%s", with value "%s".', $set['name'], $set['data']));

        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);
    }

    /**
     * @param bool $errorOnHash
     */
    public function setErrorOnHash(bool $errorOnHash): void
    {
        $this->errorOnHash = $errorOnHash;
        if (true === $errorOnHash) {
            Log::info('Will trigger duplication alert for this journal.');
        }
    }

    /**
     * Set the user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->currencyRepository->setUser($this->user);
        $this->tagFactory->setUser($user);
        $this->billRepository->setUser($this->user);
        $this->budgetRepository->setUser($this->user);
        $this->categoryRepository->setUser($this->user);
        $this->piggyRepository->setUser($this->user);
        $this->accountRepository->setUser($this->user);
    }
}
