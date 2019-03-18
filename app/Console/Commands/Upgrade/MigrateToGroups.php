<?php
/**
 * MigrateToGroups.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Upgrade;

use Exception;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use Illuminate\Console\Command;
use Log;

/**
 * This command will take split transactions and migrate them to "transaction groups".
 *
 * It will only run once, but can be forced to run again.
 *
 * Class MigrateToGroups
 */
class MigrateToGroups extends Command
{
    public const CONFIG_NAME = '4780_migrated_to_groups';
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
    protected $signature = 'firefly-iii:migrate-to-groups {--F|force : Force the migration, even if it fired before.}';

    /** @var TransactionJournalFactory */
    private $journalFactory;

    /** @var JournalRepositoryInterface */
    private $journalRepository;

    /** @var JournalDestroyService */
    private $service;

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
        $this->service           = app(JournalDestroyService::class);
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        if ($this->isMigrated() && true !== $this->option('force')) {
            $this->info('Database already seems to be migrated.');

            return 0;
        }
        if (true === $this->option('force')) {
            $this->warn('Forcing the migration.');
        }

        Log::debug('---- start group migration ----');
        $this->makeGroups();
        Log::debug('---- end group migration ----');

        $this->markAsMigrated();

        return 0;
    }

    /**
     * @return bool
     */
    private function isMigrated(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws Exception
     */
    private function makeGroup(TransactionJournal $journal): void
    {
        // double check transaction count.
        if ($journal->transactions->count() <= 2) {
            Log::debug(sprintf('Will not try to convert journal #%d because it has 2 or less transactions.', $journal->id));

            return;
        }
        Log::debug(sprintf('Will now try to convert journal #%d', $journal->id));

        $this->journalRepository->setUser($journal->user);
        $this->journalFactory->setUser($journal->user);

        $data = [
            // mandatory fields.
            'type'         => strtolower($journal->transactionType->type),
            'date'         => $journal->date,
            'user'         => $journal->user_id,
            'group_title'  => $journal->description,
            'transactions' => [],
        ];

        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        Log::debug(sprintf('Will use %d positive transactions to create a new group.', $transactions->count()));

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            Log::debug(sprintf('Now going to add transaction #%d to the array.', $transaction->id));
            $budgetId   = $this->journalRepository->getJournalBudgetId($journal);
            $categoryId = $this->journalRepository->getJournalCategoryId($journal);
            $opposingTr = $this->journalRepository->findOpposingTransaction($transaction);

            if (null === $opposingTr) {
                $this->error(
                    sprintf(
                        'Journal #%d has no opposing transaction for transaction #%d. Cannot upgrade this entry.',
                        $journal->id, $transaction->id
                    )
                );
                continue;
            }

            $tArray = [
                'currency_id'         => $transaction->transaction_currency_id,
                'foreign_currency_id' => $transaction->foreign_currency_id,
                'amount'              => $transaction->amount,
                'foreign_amount'      => $transaction->foreign_amount,
                'description'         => $transaction->description ?? $journal->description,
                'source_id'           => $opposingTr->account_id,
                'destination_id'      => $transaction->account_id,
                'budget_id'           => $budgetId,
                'category_id'         => $categoryId,
                'bill_id'             => $journal->bill_id,
                'notes'               => $this->journalRepository->getNoteText($journal),
                'tags'                => $this->journalRepository->getTags($journal),
                'internal_reference'  => $this->journalRepository->getMetaField($journal, 'internal-reference'),
                'sepa-cc'             => $this->journalRepository->getMetaField($journal, 'sepa-cc'),
                'sepa-ct-op'          => $this->journalRepository->getMetaField($journal, 'sepa-ct-op'),
                'sepa-ct-id'          => $this->journalRepository->getMetaField($journal, 'sepa-ct-id'),
                'sepa-db'             => $this->journalRepository->getMetaField($journal, 'sepa-db'),
                'sepa-country'        => $this->journalRepository->getMetaField($journal, 'sepa-country'),
                'sepa-ep'             => $this->journalRepository->getMetaField($journal, 'sepa-ep'),
                'sepa-ci'             => $this->journalRepository->getMetaField($journal, 'sepa-ci'),
                'sepa-batch-id'       => $this->journalRepository->getMetaField($journal, 'sepa-batch-id'),
                'external_id'         => $this->journalRepository->getMetaField($journal, 'external-id'),
                'original-source'     => $this->journalRepository->getMetaField($journal, 'original-source'),
                'recurrence_id'       => $this->journalRepository->getMetaField($journal, 'recurrence_id'),
                'bunq_payment_id'     => $this->journalRepository->getMetaField($journal, 'bunq_payment_id'),
                'importHash'          => $this->journalRepository->getMetaField($journal, 'importHash'),
                'importHashV2'        => $this->journalRepository->getMetaField($journal, 'importHashV2'),
                'interest_date'       => $this->journalRepository->getMetaDate($journal, 'interest_date'),
                'book_date'           => $this->journalRepository->getMetaDate($journal, 'book_date'),
                'process_date'        => $this->journalRepository->getMetaDate($journal, 'process_date'),
                'due_date'            => $this->journalRepository->getMetaDate($journal, 'due_date'),
                'payment_date'        => $this->journalRepository->getMetaDate($journal, 'payment_date'),
                'invoice_date'        => $this->journalRepository->getMetaDate($journal, 'invoice_date'),
            ];

            $data['transactions'][] = $tArray;
        }
        Log::debug(sprintf('Now calling transaction journal factory (%d transactions in array)', count($data['transactions'])));
        $result = $this->journalFactory->create($data);
        Log::debug('Done calling transaction journal factory');

        // delete the old transaction journal.
        //$this->service->destroy($journal);

        // report on result:
        Log::debug(sprintf('Migrated journal #%d into these journals: %s', $journal->id, implode(', ', $result->pluck('id')->toArray())));
        $this->line(sprintf('Migrated journal #%d into these journals: %s', $journal->id, implode(', ', $result->pluck('id')->toArray())));
    }

    /**
     *
     * @throws Exception
     */
    private function makeGroups(): void
    {
        $splitJournals = $this->journalRepository->getSplitJournals();

        if ($splitJournals->count() > 0) {
            $this->info(sprintf('Going to un-split %d transaction(s). This could take some time.', $splitJournals->count()));
            /** @var TransactionJournal $journal */
            foreach ($splitJournals as $journal) {
                $this->makeGroup($journal);
            }
        }
        if (0 === $splitJournals->count()) {
            $this->info('Found no split journals. Nothing to do.');
        }
    }

    /**
     *
     */
    private function markAsMigrated(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

}
