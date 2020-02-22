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

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
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

    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var AccountValidator */
    private $accountValidator;
    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var array */
    private $fields;
    /** @var PiggyBankEventFactory */
    private $piggyEventFactory;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepository;
    /** @var TransactionFactory */
    private $transactionFactory;
    /** @var TransactionTypeRepositoryInterface */
    private $typeRepository;
    /** @var User The user */
    private $user;
    /** @var bool */
    private $errorOnHash;

    /**
     * Constructor.
     *
     * @throws Exception
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->errorOnHash = false;
        $this->fields      = [
            // sepa
            'sepa_cc', 'sepa_ct_op', 'sepa_ct_id',
            'sepa_db', 'sepa_country', 'sepa_ep',
            'sepa_ci', 'sepa_batch_id',

            // dates
            'interest_date', 'book_date', 'process_date',
            'due_date', 'payment_date', 'invoice_date',

            // others
            'recurrence_id', 'internal_reference', 'bunq_payment_id',
            'import_hash', 'import_hash_v2', 'external_id', 'original_source'];


        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }

        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->typeRepository     = app(TransactionTypeRepositoryInterface::class);
        $this->transactionFactory = app(TransactionFactory::class);
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
     * @throws DuplicateTransactionException
     */
    public function create(array $data): Collection
    {
        // convert to special object.
        $dataObject = new NullArrayObject($data);

        Log::debug('Start of TransactionJournalFactory::create()');
        $collection   = new Collection;
        $transactions = $dataObject['transactions'] ?? [];
        if (0 === count($transactions)) {
            Log::error('There are no transactions in the array, the TransactionJournalFactory cannot continue.');

            return new Collection;
        }

        /** @var array $row */
        foreach ($transactions as $index => $row) {
            Log::debug(sprintf('Now creating journal %d/%d', $index + 1, count($transactions)));

            Log::debug('Going to call createJournal', $row);
            try {
                $journal = $this->createJournal(new NullArrayObject($row));
            } catch (DuplicateTransactionException|Exception $e) {
                Log::warning('TransactionJournalFactory::create() caught a duplicate journal in createJournal()');
                throw new DuplicateTransactionException($e->getMessage());
            }
            if (null !== $journal) {
                $collection->push($journal);
            }
            if (null === $journal) {
                Log::error('The createJournal() method returned NULL. This may indicate an error.');
            }
        }

        return $collection;
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
        $this->transactionFactory->setUser($this->user);
        $this->billRepository->setUser($this->user);
        $this->budgetRepository->setUser($this->user);
        $this->categoryRepository->setUser($this->user);
        $this->piggyRepository->setUser($this->user);
        $this->accountRepository->setUser($this->user);
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
     * @param NullArrayObject $row
     *
     * @return TransactionJournal|null
     * @throws Exception
     * @throws DuplicateTransactionException
     */
    private function createJournal(NullArrayObject $row): ?TransactionJournal
    {
        $row['import_hash_v2'] = $this->hashArray($row);

        $this->errorIfDuplicate($row['import_hash_v2']);

        /** Some basic fields */
        $type            = $this->typeRepository->findTransactionType(null, $row['type']);
        $carbon          = $row['date'] ?? new Carbon;
        $order           = $row['order'] ?? 0;
        $currency        = $this->currencyRepository->findCurrency((int)$row['currency_id'], $row['currency_code']);
        $foreignCurrency = $this->currencyRepository->findCurrencyNull($row['foreign_currency_id'], $row['foreign_currency_code']);
        $bill            = $this->billRepository->findBill((int)$row['bill_id'], $row['bill_name']);
        $billId          = TransactionType::WITHDRAWAL === $type->type && null !== $bill ? $bill->id : null;
        $description     = app('steam')->cleanString((string)$row['description']);

        /** Manipulate basic fields */
        $carbon->setTimezone(config('app.timezone'));

        /** Get source + destination account */
        Log::debug(sprintf('Currency is #%d (%s)', $currency->id, $currency->code));

        try {
            // validate source and destination using a new Validator.
            $this->validateAccounts($row);
            /** create or get source and destination accounts  */

            $sourceInfo = [
                'id'     => (int)$row['source_id'],
                'name'   => $row['source_name'],
                'iban'   => $row['source_iban'],
                'number' => $row['source_number'],
                'bic'    => $row['source_bic'],
            ];

            $destInfo = [
                'id'     => (int)$row['destination_id'],
                'name'   => $row['destination_name'],
                'iban'   => $row['destination_iban'],
                'number' => $row['destination_number'],
                'bic'    => $row['destination_bic'],
            ];
            Log::debug('Source info:', $sourceInfo);
            Log::debug('Destination info:', $destInfo);

            $sourceAccount      = $this->getAccount($type->type, 'source', $sourceInfo);
            $destinationAccount = $this->getAccount($type->type, 'destination', $destInfo);
            // @codeCoverageIgnoreStart
        } catch (FireflyException $e) {
            Log::error('Could not validate source or destination.');
            Log::error($e->getMessage());

            return null;
        }
        // @codeCoverageIgnoreEnd

        // TODO AFTER 4.8,0 better handling below:

        /** double check currencies. */
        $sourceCurrency        = $currency;
        $destCurrency          = $currency;
        $sourceForeignCurrency = $foreignCurrency;
        $destForeignCurrency   = $foreignCurrency;

        if (TransactionType::WITHDRAWAL === $type->type) {
            // make sure currency is correct.
            $currency = $this->getCurrency($currency, $sourceAccount);
            // make sure foreign currency != currency.
            if (null !== $foreignCurrency && $foreignCurrency->id === $currency->id) {
                $foreignCurrency = null;
            }
            $sourceCurrency        = $currency;
            $destCurrency          = $currency;
            $sourceForeignCurrency = $foreignCurrency;
            $destForeignCurrency   = $foreignCurrency;
        }
        if (TransactionType::DEPOSIT === $type->type) {
            // make sure currency is correct.
            $currency = $this->getCurrency($currency, $destinationAccount);
            // make sure foreign currency != currency.
            if (null !== $foreignCurrency && $foreignCurrency->id === $currency->id) {
                $foreignCurrency = null;
            }

            $sourceCurrency        = $currency;
            $destCurrency          = $currency;
            $sourceForeignCurrency = $foreignCurrency;
            $destForeignCurrency   = $foreignCurrency;
        }

        if (TransactionType::TRANSFER === $type->type) {
            // get currencies
            $currency        = $this->getCurrency($currency, $sourceAccount);
            $foreignCurrency = $this->getCurrency($foreignCurrency, $destinationAccount);

            $sourceCurrency        = $currency;
            $destCurrency          = $currency;
            $sourceForeignCurrency = $foreignCurrency;
            $destForeignCurrency   = $foreignCurrency;
        }

        $description = '' === $description ? '(empty description)' : $description;
        $description = substr($description, 0, 255);


        /** Create a basic journal. */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $type->id,
                'bill_id'                 => $billId,
                'transaction_currency_id' => $currency->id,
                'description'             => substr($description,0,1000),
                'date'                    => $carbon->format('Y-m-d H:i:s'),
                'order'                   => $order,
                'tag_count'               => 0,
                'completed'               => 0,
            ]
        );
        Log::debug(sprintf('Created new journal #%d: "%s"', $journal->id, $journal->description));

        /** Create two transactions. */
        /** @var TransactionFactory $transactionFactory */
        $transactionFactory = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($sourceAccount);
        $transactionFactory->setCurrency($sourceCurrency);
        $transactionFactory->setForeignCurrency($sourceForeignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);
        $transactionFactory->createNegative((string)$row['amount'], (string)$row['foreign_amount']);

        // and the destination one:
        /** @var TransactionFactory $transactionFactory */
        $transactionFactory = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($destinationAccount);
        $transactionFactory->setCurrency($destCurrency);
        $transactionFactory->setForeignCurrency($destForeignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);
        $transactionFactory->createPositive((string)$row['amount'], (string)$row['foreign_amount']);

        // verify that journal has two transactions. Otherwise, delete and cancel.
        // TODO this can't be faked so it can't be tested.
        //        $count = $journal->transactions()->count();
        //        if (2 !== $count) {
        //            // @codeCoverageIgnoreStart
        //            Log::error(sprintf('The journal unexpectedly has %d transaction(s). This is not OK. Cancel operation.', $count));
        //            try {
        //                $journal->delete();
        //            } catch (Exception $e) {
        //                Log::debug(sprintf('Dont care: %s.', $e->getMessage()));
        //            }
        //
        //            return null;
        //            // @codeCoverageIgnoreEnd
        //        }
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
            Log::warning('Found a duplicate!');
            throw new DuplicateTransactionException(sprintf('Duplicate of transaction #%d.', $result->transactionJournal->transaction_group_id));
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
        $preference = $this->accountRepository->getAccountCurrency($account);
        if (null === $preference && null === $currency) {
            // return user's default:
            return app('amount')->getDefaultCurrencyByUser($this->user);
        }
        $result = $preference ?? $currency;
        Log::debug(sprintf('Currency is now #%d (%s) because of account #%d (%s)', $result->id, $result->code, $account->id, $account->name));

        return $result;
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
        $json = json_encode($dataRow);
        if (false === $json) {
            // @codeCoverageIgnoreStart
            $json = json_encode((string)microtime());
            Log::error(sprintf('Could not hash the original row! %s', json_last_error_msg()), $dataRow);
            // @codeCoverageIgnoreEnd
        }
        $hash = hash('sha256', $json);
        Log::debug(sprintf('The hash is: %s', $hash));

        return $hash;
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
     * @param NullArrayObject $data
     *
     * @throws FireflyException
     */
    private function validateAccounts(NullArrayObject $data): void
    {
        $transactionType = $data['type'] ?? 'invalid';
        $this->accountValidator->setUser($this->user);
        $this->accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId    = isset($data['source_id']) ? (int)$data['source_id'] : null;
        $sourceName  = $data['source_name'] ?? null;
        $validSource = $this->accountValidator->validateSource($sourceId, $sourceName);

        // do something with result:
        if (false === $validSource) {
            throw new FireflyException(sprintf('Source: %s', $this->accountValidator->sourceError)); // @codeCoverageIgnore
        }
        Log::debug('Source seems valid.');
        // validate destination account
        $destinationId    = isset($data['destination_id']) ? (int)$data['destination_id'] : null;
        $destinationName  = $data['destination_name'] ?? null;
        $validDestination = $this->accountValidator->validateDestination($destinationId, $destinationName);
        // do something with result:
        if (false === $validDestination) {
            throw new FireflyException(sprintf('Destination: %s', $this->accountValidator->destError)); // @codeCoverageIgnore
        }
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


}
