<?php

/**
 * TransactionJournalFactory.php
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

namespace FireflyIII\Factory;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use function nspl\ds\defaultarray;

/**
 * Class TransactionJournalFactory
 */
class TransactionJournalFactory
{
    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var BudgetRepositoryInterface */
    private $budgetRepository;
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var array */
    private $fields;
    /** @var PiggyBankEventFactory */
    private $piggyEventFactory;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepository;
    /** @var TagFactory */
    private $tagFactory;
    /** @var TransactionFactory */
    private $transactionFactory;
    /** @var TransactionTypeRepositoryInterface */
    private $typeRepository;
    /** @var User The user */
    private $user;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->fields = ['sepa-cc', 'sepa-ct-op', 'sepa-ct-id', 'sepa-db', 'sepa-country', 'sepa-ep', 'sepa-ci', 'interest_date', 'book_date', 'process_date',
                         'due_date', 'recurrence_id', 'payment_date', 'invoice_date', 'internal_reference', 'bunq_payment_id', 'importHash', 'importHashV2',
                         'external_id', 'sepa-batch-id', 'original-source'];


        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
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
    }

    /**
     * Store a new transaction journal.
     *
     * @param array $data
     *
     * @return Collection
     * @throws Exception
     */
    public function create(array $data): Collection
    {
        $data = new NullArrayObject($data);
        Log::debug('Start of TransactionJournalFactory::create()');
        $collection   = new Collection;
        $transactions = $data['transactions'] ?? [];
        $type         = $this->typeRepository->findTransactionType(null, $data['type']);
        $carbon       = $data['date'] ?? new Carbon;
        $carbon->setTimezone(config('app.timezone'));

        Log::debug(sprintf('Going to store a %s.', $type->type));

        if (0 === \count($transactions)) {
            Log::error('There are no transactions in the array, the TransactionJournalFactory cannot continue.');

            return new Collection;
        }

        /** @var array $row */
        foreach ($transactions as $index => $row) {
            $transaction = new NullArrayObject($row);
            Log::debug(sprintf('Now creating journal %d/%d', $index + 1, \count($transactions)));
            /** Get basic fields */

            $currency        = $this->currencyRepository->findCurrency(
                $transaction['currency'], (int)$transaction['currency_id'], $transaction['currency_code']
            );
            $foreignCurrency = $this->findForeignCurrency($transaction);

            $bill        = $this->billRepository->findBill($transaction['bill'], (int)$transaction['bill_id'], $transaction['bill_name']);
            $billId      = TransactionType::WITHDRAWAL === $type->type && null !== $bill ? $bill->id : null;
            $description = app('steam')->cleanString((string)$transaction['description']);

            /** Create a basic journal. */
            $journal = TransactionJournal::create(
                [
                    'user_id'                 => $this->user->id,
                    'transaction_type_id'     => $type->id,
                    'bill_id'                 => $billId,
                    'transaction_currency_id' => $currency->id,
                    'description'             => '' === $description ? '(empty description)' : $description,
                    'date'                    => $carbon->format('Y-m-d H:i:s'),
                    'order'                   => 0,
                    'tag_count'               => 0,
                    'completed'               => 0,
                ]
            );
            Log::debug(sprintf('Created new journal #%d: "%s"', $journal->id, $journal->description));

            /** Create two transactions. */
            $this->transactionFactory->setJournal($journal);
            $this->transactionFactory->createPair($transaction, $currency, $foreignCurrency);

            // verify that journal has two transactions. Otherwise, delete and cancel.
            $count = $journal->transactions()->count();
            if (2 !== $count) {
                // @codeCoverageIgnoreStart
                Log::error(sprintf('The journal unexpectedly has %d transaction(s). This is not OK. Cancel operation.', $count));
                $journal->delete();

                return new Collection;
                // @codeCoverageIgnoreEnd
            }

            /** Link all other data to the journal. */

            /** Link budget */
            $this->storeBudget($journal, $transaction);

            /** Link category */
            $this->storeCategory($journal, $transaction);

            /** Set notes */
            $this->storeNote($journal, $transaction['notes']);

            /** Set piggy bank */
            $this->storePiggyEvent($journal, $transaction);

            /** Set tags */
            $this->storeTags($journal, $transaction['tags']);

            /** set all meta fields */
            $this->storeMetaFields($journal, $transaction);

            $collection->push($journal);
        }

        $this->storeGroup($collection, $data['group_title']);

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
        $this->transactionFactory->setUser($this->user);
        $this->billRepository->setUser($this->user);
        $this->budgetRepository->setUser($this->user);
        $this->categoryRepository->setUser($this->user);
        $this->piggyRepository->setUser($this->user);
    }

    /**
     * Join multiple journals in a group.
     *
     * @param Collection  $collection
     * @param string|null $title
     *
     * @return TransactionGroup|null
     */
    public function storeGroup(Collection $collection, ?string $title): ?TransactionGroup
    {
        if ($collection->count() < 2) {
            return null; // @codeCoverageIgnore
        }
        /** @var TransactionJournal $first */
        $first = $collection->first();
        $group = new TransactionGroup;
        $group->user()->associate($first->user);
        $group->title = $title ?? $first->description;
        $group->save();

        $group->transactionJournals()->saveMany($collection);

        return $group;
    }

    /**
     * Link a piggy bank to this journal.
     *
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     */
    public function storePiggyEvent(TransactionJournal $journal, NullArrayObject $data): void
    {
        Log::debug('Will now store piggy event.');
        if (!$journal->isTransfer()) {
            Log::debug('Journal is not a transfer, do nothing.');

            return;
        }

        $piggyBank = $this->piggyRepository->findPiggyBank($data['piggy_bank'], (int)$data['piggy_bank_id'], $data['piggy_bank_name']);

        if (null !== $piggyBank) {
            $this->piggyEventFactory->create($journal, $piggyBank);
            Log::debug('Create piggy event.');

            return;
        }
        Log::debug('Create no piggy event');
    }

    /**
     * Link tags to journal.
     *
     * @param TransactionJournal $journal
     * @param array              $tags
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function storeTags(TransactionJournal $journal, ?array $tags): void
    {
        $this->tagFactory->setUser($journal->user);
        $set = [];
        if (!\is_array($tags)) {
            return;
        }
        foreach ($tags as $string) {
            if ('' !== $string) {
                $tag = $this->tagFactory->findOrCreate($string);
                if (null !== $tag) {
                    $set[] = $tag->id;
                }
            }
        }
        $journal->tags()->sync($set);
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
     * @param TransactionJournal $journal
     * @param string             $notes
     */
    protected function storeNote(TransactionJournal $journal, ?string $notes): void
    {
        $notes = (string)$notes;
        if ('' !== $notes) {
            $note = $journal->notes()->first();
            if (null === $note) {
                $note = new Note;
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();
            Log::debug(sprintf('Stored notes for journal #%d', $journal->id));

            return;
        }
    }

    /**
     * This is a separate function because "findCurrency" will default to EUR and that may not be what we want.
     *
     * @param NullArrayObject $transaction
     *
     * @return TransactionCurrency|null
     */
    private function findForeignCurrency(NullArrayObject $transaction): ?TransactionCurrency
    {
        if (null === $transaction['foreign_currency'] && null === $transaction['foreign_currency_id'] && null === $transaction['foreign_currency_code']) {
            return null;
        }

        return $this->currencyRepository->findCurrency(
            $transaction['foreign_currency'], (int)$transaction['foreign_currency_id'], $transaction['foreign_currency_code']
        );
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     */
    private function storeBudget(TransactionJournal $journal, NullArrayObject $data): void
    {
        $budget = $this->budgetRepository->findBudget($data['budget'], $data['budget_id'], $data['budget_name']);
        if (null !== $budget) {
            Log::debug(sprintf('Link budget #%d to journal #%d', $budget->id, $journal->id));
            $journal->budgets()->sync([$budget->id]);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     */
    private function storeCategory(TransactionJournal $journal, NullArrayObject $data): void
    {
        $category = $this->categoryRepository->findCategory($data['category'], $data['category_id'], $data['category_name']);
        if (null !== $category) {
            Log::debug(sprintf('Link category #%d to journal #%d', $category->id, $journal->id));
            $journal->categories()->sync([$category->id]);
        }
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


}
