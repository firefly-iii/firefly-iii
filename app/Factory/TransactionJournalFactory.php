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
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Location;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class TransactionJournalFactory
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
     * @throws \Exception
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
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function create(array $data): Collection
    {
        app('log')->debug('Now in TransactionJournalFactory::create()');
        // convert to special object.
        $dataObject   = new NullArrayObject($data);

        app('log')->debug('Start of TransactionJournalFactory::create()');
        $collection   = new Collection();
        $transactions = $dataObject['transactions'] ?? [];
        if (0 === count($transactions)) {
            app('log')->error('There are no transactions in the array, the TransactionJournalFactory cannot continue.');

            return new Collection();
        }

        try {
            /** @var array $row */
            foreach ($transactions as $index => $row) {
                app('log')->debug(sprintf('Now creating journal %d/%d', $index + 1, count($transactions)));
                $journal = $this->createJournal(new NullArrayObject($row));
                if (null !== $journal) {
                    $collection->push($journal);
                }
                if (null === $journal) {
                    app('log')->error('The createJournal() method returned NULL. This may indicate an error.');
                }
            }
        } catch (DuplicateTransactionException $e) {
            app('log')->warning('TransactionJournalFactory::create() caught a duplicate journal in createJournal()');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $this->forceDeleteOnError($collection);

            throw new DuplicateTransactionException($e->getMessage(), 0, $e);
        } catch (FireflyException $e) {
            app('log')->warning('TransactionJournalFactory::create() caught an exception.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $this->forceDeleteOnError($collection);

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $collection;
    }

    /**
     * TODO typeOverrule: the account validator may have another opinion on the transaction type. not sure what to do
     * with this.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function createJournal(NullArrayObject $row): ?TransactionJournal
    {
        $row['import_hash_v2'] = $this->hashArray($row);

        $this->errorIfDuplicate($row['import_hash_v2']);

        // Some basic fields
        $type                  = $this->typeRepository->findTransactionType(null, $row['type']);
        $carbon                = $row['date'] ?? today(config('app.timezone'));
        $order                 = $row['order'] ?? 0;
        $currency              = $this->currencyRepository->findCurrency((int) $row['currency_id'], $row['currency_code']);
        $foreignCurrency       = $this->currencyRepository->findCurrencyNull($row['foreign_currency_id'], $row['foreign_currency_code']);
        $bill                  = $this->billRepository->findBill((int) $row['bill_id'], $row['bill_name']);
        $billId                = TransactionTypeEnum::WITHDRAWAL->value === $type->type && null !== $bill ? $bill->id : null;
        $description           = (string) $row['description'];

        // Manipulate basic fields
        $carbon->setTimezone(config('app.timezone'));

        // 2024-11-19, overrule timezone with UTC and store it as UTC.

        if (true === FireflyConfig::get('utc', false)->data) {
            $carbon->setTimezone('UTC');
        }
        // $carbon->setTimezone('UTC');

        try {
            // validate source and destination using a new Validator.
            $this->validateAccounts($row);
        } catch (FireflyException $e) {
            app('log')->error('Could not validate source or destination.');
            app('log')->error($e->getMessage());

            return null;
        }

        /** create or get source and destination accounts  */
        $sourceInfo            = [
            'id'          => $row['source_id'],
            'name'        => $row['source_name'],
            'iban'        => $row['source_iban'],
            'number'      => $row['source_number'],
            'bic'         => $row['source_bic'],
            'currency_id' => $currency->id,
        ];

        $destInfo              = [
            'id'          => $row['destination_id'],
            'name'        => $row['destination_name'],
            'iban'        => $row['destination_iban'],
            'number'      => $row['destination_number'],
            'bic'         => $row['destination_bic'],
            'currency_id' => $currency->id,
        ];
        app('log')->debug('Source info:', $sourceInfo);
        app('log')->debug('Destination info:', $destInfo);
        $sourceAccount         = $this->getAccount($type->type, 'source', $sourceInfo);
        $destinationAccount    = $this->getAccount($type->type, 'destination', $destInfo);
        app('log')->debug('Done with getAccount(2x)');

        // this is the moment for a reconciliation sanity check (again).
        if (TransactionTypeEnum::RECONCILIATION->value === $type->type) {
            [$sourceAccount, $destinationAccount] = $this->reconciliationSanityCheck($sourceAccount, $destinationAccount);
        }

        $currency              = $this->getCurrencyByAccount($type->type, $currency, $sourceAccount, $destinationAccount);
        $foreignCurrency       = $this->compareCurrencies($currency, $foreignCurrency);
        $foreignCurrency       = $this->getForeignByAccount($type->type, $foreignCurrency, $destinationAccount);
        $description           = $this->getDescription($description);

        app('log')->debug(sprintf('Date: %s (%s)', $carbon->toW3cString(), $carbon->getTimezone()->getName()));

        /** Create a basic journal. */
        $journal               = TransactionJournal::create(
            [
                'user_id'                 => $this->user->id,
                'user_group_id'           => $this->user->user_group_id,
                'transaction_type_id'     => $type->id,
                'bill_id'                 => $billId,
                'transaction_currency_id' => $currency->id,
                'description'             => substr($description, 0, 1000),
                'date'                    => $carbon,
                'date_tz'                 => $carbon->format('e'),
                'order'                   => $order,
                'tag_count'               => 0,
                'completed'               => 0,
            ]
        );
        app('log')->debug(sprintf('Created new journal #%d: "%s"', $journal->id, $journal->description));

        /** Create two transactions. */
        $transactionFactory    = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($sourceAccount);
        $transactionFactory->setCurrency($currency);
        $transactionFactory->setAccountInformation($sourceInfo);
        $transactionFactory->setForeignCurrency($foreignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);

        try {
            $negative = $transactionFactory->createNegative((string) $row['amount'], (string) $row['foreign_amount']);
        } catch (FireflyException $e) {
            app('log')->error(sprintf('Exception creating negative transaction: %s', $e->getMessage()));
            $this->forceDeleteOnError(new Collection([$journal]));

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        /** @var TransactionFactory $transactionFactory */
        $transactionFactory    = app(TransactionFactory::class);
        $transactionFactory->setUser($this->user);
        $transactionFactory->setJournal($journal);
        $transactionFactory->setAccount($destinationAccount);
        $transactionFactory->setAccountInformation($destInfo);
        $transactionFactory->setCurrency($currency);
        $transactionFactory->setForeignCurrency($foreignCurrency);
        $transactionFactory->setReconciled($row['reconciled'] ?? false);

        // if the foreign currency is set and is different, and the transaction type is a transfer,
        // Firefly III will save the foreign currency information in such a way that both
        // asset accounts can look at the "amount" and "transaction_currency_id" column and
        // see the currency they expect to see.
        $amount                = (string) $row['amount'];
        $foreignAmount         = (string) $row['foreign_amount'];
        if (null !== $foreignCurrency && $foreignCurrency->id !== $currency->id
            && TransactionTypeEnum::TRANSFER->value === $type->type
        ) {
            $transactionFactory->setCurrency($foreignCurrency);
            $transactionFactory->setForeignCurrency($currency);
            $amount        = (string) $row['foreign_amount'];
            $foreignAmount = (string) $row['amount'];
            Log::debug('Swap native/foreign amounts in transfer for new save method.');
        }

        try {
            $transactionFactory->createPositive($amount, $foreignAmount);
        } catch (FireflyException $e) {
            app('log')->error(sprintf('Exception creating positive transaction: %s', $e->getMessage()));
            $this->forceTrDelete($negative);
            $this->forceDeleteOnError(new Collection([$journal]));

            throw new FireflyException($e->getMessage(), 0, $e);
        }
        $journal->completed    = true;
        $journal->save();
        $this->storeBudget($journal, $row);
        $this->storeCategory($journal, $row);
        $this->storeNotes($journal, $row['notes']);
        $this->storePiggyEvent($journal, $row);
        $this->storeTags($journal, $row['tags']);
        $this->storeMetaFields($journal, $row);
        $this->storeLocation($journal, $row);

        return $journal;
    }

    private function hashArray(NullArrayObject $row): string
    {
        $dataRow = $row->getArrayCopy();

        unset($dataRow['import_hash_v2'], $dataRow['original_source']);

        try {
            $json = json_encode($dataRow, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            app('log')->error(sprintf('Could not encode dataRow: %s', $e->getMessage()));
            $json = microtime();
        }
        $hash    = hash('sha256', $json);
        app('log')->debug(sprintf('The hash is: %s', $hash), $dataRow);

        return $hash;
    }

    /**
     * If this transaction already exists, throw an error.
     *
     * @throws DuplicateTransactionException
     */
    private function errorIfDuplicate(string $hash): void
    {
        app('log')->debug(sprintf('In errorIfDuplicate(%s)', $hash));
        if (false === $this->errorOnHash) {
            return;
        }
        app('log')->debug('Will verify duplicate!');

        /** @var null|TransactionJournalMeta $result */
        $result = TransactionJournalMeta::withTrashed()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->whereNotNull('transaction_journals.id')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('data', json_encode($hash, JSON_THROW_ON_ERROR))
            ->with(['transactionJournal', 'transactionJournal.transactionGroup'])
            ->first(['journal_meta.*'])
        ;
        if (null !== $result) {
            app('log')->warning(sprintf('Found a duplicate in errorIfDuplicate because hash %s is not unique!', $hash));
            $journal = $result->transactionJournal()->withTrashed()->first();
            $group   = $journal?->transactionGroup()->withTrashed()->first();
            $groupId = (int) $group?->id;

            throw new DuplicateTransactionException(sprintf('Duplicate of transaction #%d.', $groupId));
        }
    }

    /**
     * @throws FireflyException
     */
    private function validateAccounts(NullArrayObject $data): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $transactionType  = $data['type'] ?? 'invalid';
        $this->accountValidator->setUser($this->user);
        $this->accountValidator->setTransactionType($transactionType);

        // validate source account.
        $array            = [
            'id'     => null !== $data['source_id'] ? (int) $data['source_id'] : null,
            'name'   => null !== $data['source_name'] ? (string) $data['source_name'] : null,
            'iban'   => null !== $data['source_iban'] ? (string) $data['source_iban'] : null,
            'number' => null !== $data['source_number'] ? (string) $data['source_number'] : null,
        ];
        $validSource      = $this->accountValidator->validateSource($array);

        // do something with result:
        if (false === $validSource) {
            throw new FireflyException(sprintf('Source: %s', $this->accountValidator->sourceError));
        }
        app('log')->debug('Source seems valid.');

        // validate destination account
        $array            = [
            'id'     => null !== $data['destination_id'] ? (int) $data['destination_id'] : null,
            'name'   => null !== $data['destination_name'] ? (string) $data['destination_name'] : null,
            'iban'   => null !== $data['destination_iban'] ? (string) $data['destination_iban'] : null,
            'number' => null !== $data['destination_number'] ? (string) $data['destination_number'] : null,
        ];

        $validDestination = $this->accountValidator->validateDestination($array);
        // do something with result:
        if (false === $validDestination) {
            throw new FireflyException(sprintf('Destination: %s', $this->accountValidator->destError));
        }
    }

    /**
     * Set the user.
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

    private function reconciliationSanityCheck(?Account $sourceAccount, ?Account $destinationAccount): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        if (null !== $sourceAccount && null !== $destinationAccount) {
            app('log')->debug('Both accounts exist, simply return them.');

            return [$sourceAccount, $destinationAccount];
        }
        if (null === $destinationAccount) {
            app('log')->debug('Destination account is NULL, source account is not.');
            $account = $this->accountRepository->getReconciliation($sourceAccount);
            app('log')->debug(sprintf('Will return account #%d ("%s") of type "%s"', $account->id, $account->name, $account->accountType->type));

            return [$sourceAccount, $account];
        }

        if (null === $sourceAccount) { // @phpstan-ignore-line
            app('log')->debug('Source account is NULL, destination account is not.');
            $account = $this->accountRepository->getReconciliation($destinationAccount);
            app('log')->debug(sprintf('Will return account #%d ("%s") of type "%s"', $account->id, $account->name, $account->accountType->type));

            return [$account, $destinationAccount];
        }
        app('log')->debug('Unused fallback');  // @phpstan-ignore-line

        return [$sourceAccount, $destinationAccount];
    }

    /**
     * @throws FireflyException
     */
    private function getCurrencyByAccount(string $type, ?TransactionCurrency $currency, Account $source, Account $destination): TransactionCurrency
    {
        app('log')->debug('Now in getCurrencyByAccount()');

        return match ($type) {
            default                             => $this->getCurrency($currency, $source),
            TransactionTypeEnum::DEPOSIT->value => $this->getCurrency($currency, $destination),
        };
    }

    /**
     * @throws FireflyException
     */
    private function getCurrency(?TransactionCurrency $currency, Account $account): TransactionCurrency
    {
        app('log')->debug('Now in getCurrency()');

        /** @var null|TransactionCurrency $preference */
        $preference = $this->accountRepository->getAccountCurrency($account);
        if (null === $preference && null === $currency) {
            // return user's default:
            return app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);
        }
        $result     = $preference ?? $currency;
        app('log')->debug(sprintf('Currency is now #%d (%s) because of account #%d (%s)', $result->id, $result->code, $account->id, $account->name));

        return $result;
    }

    /**
     * Set foreign currency to NULL if it's the same as the normal currency:
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
     * @throws FireflyException
     */
    private function getForeignByAccount(string $type, ?TransactionCurrency $foreignCurrency, Account $destination): ?TransactionCurrency
    {
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            return $this->getCurrency($foreignCurrency, $destination);
        }

        return $foreignCurrency;
    }

    private function getDescription(string $description): string
    {
        $description = '' === $description ? '(empty description)' : $description;

        return substr($description, 0, 1024);
    }

    /**
     * Force the deletion of an entire set of transaction journals and their meta object in case of
     * an error creating a group.
     */
    private function forceDeleteOnError(Collection $collection): void
    {
        app('log')->debug(sprintf('forceDeleteOnError on collection size %d item(s)', $collection->count()));
        $service = app(JournalDestroyService::class);

        /** @var TransactionJournal $journal */
        foreach ($collection as $journal) {
            app('log')->debug(sprintf('forceDeleteOnError on journal #%d', $journal->id));
            $service->destroy($journal);
        }
    }

    private function forceTrDelete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    /**
     * Link a piggy bank to this journal.
     */
    private function storePiggyEvent(TransactionJournal $journal, NullArrayObject $data): void
    {
        app('log')->debug('Will now store piggy event.');

        $piggyBank = $this->piggyRepository->findPiggyBank((int) $data['piggy_bank_id'], $data['piggy_bank_name']);

        if (null !== $piggyBank) {
            $this->piggyEventFactory->create($journal, $piggyBank);
            app('log')->debug('Create piggy event.');

            return;
        }
        app('log')->debug('Create no piggy event');
    }

    private function storeMetaFields(TransactionJournal $journal, NullArrayObject $transaction): void
    {
        foreach ($this->fields as $field) {
            $this->storeMeta($journal, $transaction, $field);
        }
    }

    protected function storeMeta(TransactionJournal $journal, NullArrayObject $data, string $field): void
    {
        $set     = [
            'journal' => $journal,
            'name'    => $field,
            'data'    => (string) ($data[$field] ?? ''),
        ];
        if ($data[$field] instanceof Carbon) {
            $data[$field]->setTimezone(config('app.timezone'));
            app('log')->debug(sprintf('%s Date: %s (%s)', $field, $data[$field], $data[$field]->timezone->getName()));
            $set['data'] = $data[$field]->format('Y-m-d H:i:s');
        }

        app('log')->debug(sprintf('Going to store meta-field "%s", with value "%s".', $set['name'], $set['data']));

        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);
    }

    private function storeLocation(TransactionJournal $journal, NullArrayObject $data): void
    {
        if (true === $data['store_location']) {
            $location             = new Location();
            $location->longitude  = $data['longitude'];
            $location->latitude   = $data['latitude'];
            $location->zoom_level = $data['zoom_level'];
            $location->locatable()->associate($journal);
            $location->save();
        }
    }

    public function setErrorOnHash(bool $errorOnHash): void
    {
        $this->errorOnHash = $errorOnHash;
        if (true === $errorOnHash) {
            app('log')->info('Will trigger duplication alert for this journal.');
        }
    }
}
