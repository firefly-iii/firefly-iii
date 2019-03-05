<?php

namespace FireflyIII\Console\Commands;

use DB;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MigrateToGroups
 */
class MigrateToGroups extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates a pre-4.7.8 transaction structure to the 4.7.8+ transaction structure.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:migrate-to-groups';

    /** @var TransactionJournalFactory */
    private $journalFactory;

    /** @var JournalRepositoryInterface */
    private $journalRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->journalFactory    = app(TransactionJournalFactory::class);
        $this->journalRepository = app(JournalRepositoryInterface::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->isMigrated()) {
            $this->info('Database already seems to be migrated.');
        }
        Log::debug('---- start group migration ----');
        $this->makeGroups();

        Log::debug('---- end group migration ----');

        return 0;
    }

    /**
     * @return bool
     */
    private function isMigrated(): bool
    {
        $configName = 'migrated_to_groups_478';
        $configVar  = app('fireflyconfig')->get($configName, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function makeGroup(TransactionJournal $journal): void
    {
        // double check transaction count.
        if ($journal->transactions->count() <= 2) {
            return;
        }
        $this->journalRepository->setUser($journal->user);
        $this->journalFactory->setUser($journal->user);

        $data = [
            // mandatory fields.
            'type'               => strtolower($journal->transactionType->type),
            'date'               => $journal->date,
            'user'               => $journal->user_id,

            // currency fields:
            'currency'           => null,
            'currency_id'        => null,
            'currency_code'      => null,

            // all custom fields:
            'internal_reference' => $this->journalRepository->getMetaField($journal, 'internal-reference'),
            'sepa-cc'            => $this->journalRepository->getMetaField($journal, 'sepa-cc'),
            'sepa-ct-op'         => $this->journalRepository->getMetaField($journal, 'sepa-ct-op'),
            'sepa-ct-id'         => $this->journalRepository->getMetaField($journal, 'sepa-ct-id'),
            'sepa-db'            => $this->journalRepository->getMetaField($journal, 'sepa-db'),
            'sepa-country'       => $this->journalRepository->getMetaField($journal, 'sepa-country'),
            'sepa-ep'            => $this->journalRepository->getMetaField($journal, 'sepa-ep'),
            'sepa-ci'            => $this->journalRepository->getMetaField($journal, 'sepa-ci'),
            'sepa-batch-id'      => $this->journalRepository->getMetaField($journal, 'sepa-batch-id'),
            'interest_date'      => $this->journalRepository->getMetaDateString($journal, 'interest_date'),
            'book_date'          => $this->journalRepository->getMetaDateString($journal, 'book_date'),
            'process_date'       => $this->journalRepository->getMetaDateString($journal, 'process_date'),
            'due_date'           => $this->journalRepository->getMetaDateString($journal, 'due_date'),
            'payment_date'       => $this->journalRepository->getMetaDateString($journal, 'payment_date'),
            'invoice_date'       => $this->journalRepository->getMetaDateString($journal, 'invoice_date'),
            'external_id'        => $this->journalRepository->getMetaField($journal, 'external-id'),
            'original-source'    => $this->journalRepository->getMetaField($journal, 'original-source'),
            // journal data:
            'description'        => $journal->description,
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => $journal->bill_id,
            'bill_name'          => null,
            'tags'               => null,
            'notes'              => null,
            'transactions'       => [],
        ];


        // simply use the positive transactions as a base to create new transaction journals.
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $budgetId   = 0;
            $categoryId = 0;
            if (null !== $transaction->budgets()->first()) {
                $budgetId = $transaction->budgets()->first()->id;
            }
            if (null !== $transaction->categories()->first()) {
                $categoryId = $transaction->categories()->first()->id;
            }
            // opposing for source:
            /** @var Transaction $opposing */
            $opposing = $journal->transactions()->where('amount', $transaction->amount * -1)
                                ->where('identifier', $transaction->identifier)->first();
            if (null === $opposing) {
                $this->error(sprintf('Could not convert journal #%d', $journal->id));

                return;
            }

            $tArray = [
                'notes'                 => $this->journalRepository->getNoteText($journal),
                'tags'                  => $journal->tags->pluck('tag')->toArray(),
                'currency' 				=> null,
                'currency_id'           => $transaction->transaction_currency_id,
                'currency_code'         => null,
                'description'           => $transaction->description,
                'amount'                => $transaction->amount,
                'budget'				=> null,
                'budget_id'             => $budgetId,
                'budget_name'           => null,
                'category' 				=> null,
                'category_id'           => $categoryId,
                'category_name'         => null,
                'source' 				=> null,
                'source_id'             => $opposing->account_id,
                'source_name'           => null,
                'destination' => null,
                'destination_id'        => $transaction->account_id,
                'destination_name'      => null,
                'foreign_currency' 		=> null,
                'foreign_currency_id'   => $transaction->foreign_currency_id,
                'foreign_currency_code' => null,
                'foreign_amount'        => $transaction->foreign_amount,
                'reconciled'            => false,
            ];


            $data['transactions'][] = $tArray;
        }
        $this->journalFactory->create($data);
        // create a new transaction journal based on this particular transaction using the factory.
        // delete the old transaction journal.
        //$journal->delete();
    }

    /**
     *
     */
    private function makeGroups(): void
    {

        // grab all split transactions:
        $all = Transaction::groupBy('transaction_journal_id')
                          ->get(['transaction_journal_id', DB::raw('COUNT(transaction_journal_id) as result')]);
        /** @var Collection $filtered */
        $filtered      = $all->filter(
            function (Transaction $transaction) {
                return $transaction->result > 2;
            }
        );
        $journalIds    = array_unique($filtered->pluck('transaction_journal_id')->toArray());
        $splitJournals = TransactionJournal::whereIn('id', $journalIds)->get();
        $this->info(sprintf('Going to un-split %d transactions. This could take some time.', $splitJournals->count()));

        /** @var TransactionJournal $journal */
        foreach ($splitJournals as $journal) {
            $group = $this->makeGroup($journal);
        }

        return;

        // first run, create new transaction journals and groups for splits
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            Log::debug(sprintf('Now going to migrate journal #%d', $journal->id));
            //$this->migrateCategory($journal);
            //$this->migrateBudget($journal);
        }

    }

    /**
     * Migrate the category. This is basically a back-and-forth between the journal
     * and underlying categories.
     *
     * @param TransactionJournal $journal
     */
    private function migrateCategory(TransactionJournal $journal): void
    {
        /** @var Category $category */
        $category     = $journal->categories()->first();
        $transactions = $journal->transactions;
        $tCategory    = null;
        Log::debug(sprintf('Journal #%d has %d transactions', $journal->id, $transactions->count()));

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $tCategory = $tCategory ?? $transaction->categories()->first();
            // category and tCategory are null.
            if (null === $category && null === $tCategory) {
                Log::debug(sprintf('Transaction #%d and journal #%d both have no category set. Continue.', $transaction->id, $journal->id));
                continue;
            }
            // category is null, tCategory is not.
            if (null === $category && null !== $tCategory) {
                Log::debug(sprintf('Transaction #%d has a category but journal #%d does not. Will update journal.', $transaction->id, $journal->id));
                $journal->categories()->save($tCategory);
                $category = $tCategory;
                continue;
            }
            // tCategory is null, category is not.

            // tCategory and category are equal
            // tCategory and category are not equal
        }
    }
}
