<?php

/**
 * MigrateToGroups.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * This command will take split transactions and migrate them to "transaction groups".
 *
 * It will only run once, but can be forced to run again.
 *
 * Class MigrateToGroups
 */
class MigrateToGroups extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_migrated_to_groups';
    protected $description          = 'Migrates a pre-4.7.8 transaction structure to the 4.7.8+ transaction structure.';
    protected $signature            = 'firefly-iii:migrate-to-groups {--F|force : Force the migration, even if it fired before.}';
    private JournalCLIRepositoryInterface $cliRepository;
    private int                           $count;
    private TransactionGroupFactory       $groupFactory;
    private JournalRepositoryInterface    $journalRepository;
    private JournalDestroyService         $service;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->stupidLaravel();

        if ($this->isMigrated() && true !== $this->option('force')) {
            $this->friendlyInfo('Database is already migrated.');

            return 0;
        }

        if (true === $this->option('force')) {
            $this->friendlyWarning('Forcing the migration.');
        }

        $this->makeGroupsFromSplitJournals();
        $this->makeGroupsFromAll();

        if (0 !== $this->count) {
            $this->friendlyInfo(sprintf('Migrated %d transaction journal(s).', $this->count));
        }
        if (0 === $this->count) {
            $this->friendlyPositive('No journals to migrate to groups.');
        }
        $this->markAsMigrated();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->count             = 0;
        $this->journalRepository = app(JournalRepositoryInterface::class);
        $this->service           = app(JournalDestroyService::class);
        $this->groupFactory      = app(TransactionGroupFactory::class);
        $this->cliRepository     = app(JournalCLIRepositoryInterface::class);
    }

    private function isMigrated(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    private function makeGroupsFromSplitJournals(): void
    {
        $splitJournals = $this->cliRepository->getSplitJournals();
        if ($splitJournals->count() > 0) {
            $this->friendlyLine(sprintf('Going to convert %d split transaction(s). Please hold..', $splitJournals->count()));

            /** @var TransactionJournal $journal */
            foreach ($splitJournals as $journal) {
                $this->makeMultiGroup($journal);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function makeMultiGroup(TransactionJournal $journal): void
    {
        // double check transaction count.
        if ($journal->transactions->count() <= 2) {
            app('log')->debug(sprintf('Will not try to convert journal #%d because it has 2 or fewer transactions.', $journal->id));

            return;
        }
        app('log')->debug(sprintf('Will now try to convert journal #%d', $journal->id));

        $this->journalRepository->setUser($journal->user);
        $this->groupFactory->setUser($journal->user);
        $this->cliRepository->setUser($journal->user);

        $data             = [
            // mandatory fields.
            'group_title'  => $journal->description,
            'transactions' => [],
        ];
        $destTransactions = $this->getDestinationTransactions($journal);

        app('log')->debug(sprintf('Will use %d positive transactions to create a new group.', $destTransactions->count()));

        /** @var Transaction $transaction */
        foreach ($destTransactions as $transaction) {
            $data['transactions'][] = $this->generateTransaction($journal, $transaction);
        }
        app('log')->debug(sprintf('Now calling transaction journal factory (%d transactions in array)', count($data['transactions'])));
        $group            = $this->groupFactory->create($data);
        app('log')->debug('Done calling transaction journal factory');

        // delete the old transaction journal.
        $this->service->destroy($journal);

        ++$this->count;

        // report on result:
        app('log')->debug(
            sprintf(
                'Migrated journal #%d into group #%d with these journals: #%s',
                $journal->id,
                $group->id,
                implode(', #', $group->transactionJournals->pluck('id')->toArray())
            )
        );
        $this->friendlyInfo(
            sprintf(
                'Migrated journal #%d into group #%d with these journals: #%s',
                $journal->id,
                $group->id,
                implode(', #', $group->transactionJournals->pluck('id')->toArray())
            )
        );
    }

    private function getDestinationTransactions(TransactionJournal $journal): Collection
    {
        return $journal->transactions->filter(
            static function (Transaction $transaction) {
                return $transaction->amount > 0;
            }
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function generateTransaction(TransactionJournal $journal, Transaction $transaction): array
    {
        app('log')->debug(sprintf('Now going to add transaction #%d to the array.', $transaction->id));
        $opposingTr     = $this->findOpposingTransaction($journal, $transaction);

        if (null === $opposingTr) {
            $this->friendlyError(
                sprintf(
                    'Journal #%d has no opposing transaction for transaction #%d. Cannot upgrade this entry.',
                    $journal->id,
                    $transaction->id
                )
            );

            return [];
        }

        $budgetId       = $this->cliRepository->getJournalBudgetId($journal);
        $categoryId     = $this->cliRepository->getJournalCategoryId($journal);
        $notes          = $this->cliRepository->getNoteText($journal);
        $tags           = $this->cliRepository->getTags($journal);
        $internalRef    = $this->cliRepository->getMetaField($journal, 'internal-reference');
        $sepaCC         = $this->cliRepository->getMetaField($journal, 'sepa_cc');
        $sepaCtOp       = $this->cliRepository->getMetaField($journal, 'sepa_ct_op');
        $sepaCtId       = $this->cliRepository->getMetaField($journal, 'sepa_ct_id');
        $sepaDb         = $this->cliRepository->getMetaField($journal, 'sepa_db');
        $sepaCountry    = $this->cliRepository->getMetaField($journal, 'sepa_country');
        $sepaEp         = $this->cliRepository->getMetaField($journal, 'sepa_ep');
        $sepaCi         = $this->cliRepository->getMetaField($journal, 'sepa_ci');
        $sepaBatchId    = $this->cliRepository->getMetaField($journal, 'sepa_batch_id');
        $externalId     = $this->cliRepository->getMetaField($journal, 'external-id');
        $originalSource = $this->cliRepository->getMetaField($journal, 'original-source');
        $recurrenceId   = $this->cliRepository->getMetaField($journal, 'recurrence_id');
        $bunq           = $this->cliRepository->getMetaField($journal, 'bunq_payment_id');
        $hash           = $this->cliRepository->getMetaField($journal, 'import_hash');
        $hashTwo        = $this->cliRepository->getMetaField($journal, 'import_hash_v2');
        $interestDate   = $this->cliRepository->getMetaDate($journal, 'interest_date');
        $bookDate       = $this->cliRepository->getMetaDate($journal, 'book_date');
        $processDate    = $this->cliRepository->getMetaDate($journal, 'process_date');
        $dueDate        = $this->cliRepository->getMetaDate($journal, 'due_date');
        $paymentDate    = $this->cliRepository->getMetaDate($journal, 'payment_date');
        $invoiceDate    = $this->cliRepository->getMetaDate($journal, 'invoice_date');

        // overrule journal category with transaction category.
        $budgetId       = $this->getTransactionBudget($transaction, $opposingTr) ?? $budgetId;
        $categoryId     = $this->getTransactionCategory($transaction, $opposingTr) ?? $categoryId;

        return [
            'type'                => strtolower($journal->transactionType->type),
            'date'                => $journal->date,
            'user'                => $journal->user_id,
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
            'notes'               => $notes,
            'tags'                => $tags,
            'internal_reference'  => $internalRef,
            'sepa_cc'             => $sepaCC,
            'sepa_ct_op'          => $sepaCtOp,
            'sepa_ct_id'          => $sepaCtId,
            'sepa_db'             => $sepaDb,
            'sepa_country'        => $sepaCountry,
            'sepa_ep'             => $sepaEp,
            'sepa_ci'             => $sepaCi,
            'sepa_batch_id'       => $sepaBatchId,
            'external_id'         => $externalId,
            'original-source'     => $originalSource,
            'recurrence_id'       => $recurrenceId,
            'bunq_payment_id'     => $bunq,
            'import_hash'         => $hash,
            'import_hash_v2'      => $hashTwo,
            'interest_date'       => $interestDate,
            'book_date'           => $bookDate,
            'process_date'        => $processDate,
            'due_date'            => $dueDate,
            'payment_date'        => $paymentDate,
            'invoice_date'        => $invoiceDate,
        ];
    }

    private function findOpposingTransaction(TransactionJournal $journal, Transaction $transaction): ?Transaction
    {
        $set = $journal->transactions->filter(
            static function (Transaction $subject) use ($transaction) {
                $amount     = (float)$transaction->amount * -1 === (float)$subject->amount;  // intentional float
                $identifier = $transaction->identifier === $subject->identifier;
                app('log')->debug(sprintf('Amount the same? %s', var_export($amount, true)));
                app('log')->debug(sprintf('ID the same?     %s', var_export($identifier, true)));

                return $amount && $identifier;
            }
        );

        return $set->first();
    }

    private function getTransactionBudget(Transaction $left, Transaction $right): ?int
    {
        app('log')->debug('Now in getTransactionBudget()');

        // try to get a budget ID from the left transaction:
        /** @var null|Budget $budget */
        $budget = $left->budgets()->first();
        if (null !== $budget) {
            app('log')->debug(sprintf('Return budget #%d, from transaction #%d', $budget->id, $left->id));

            return $budget->id;
        }

        // try to get a budget ID from the right transaction:
        /** @var null|Budget $budget */
        $budget = $right->budgets()->first();
        if (null !== $budget) {
            app('log')->debug(sprintf('Return budget #%d, from transaction #%d', $budget->id, $right->id));

            return $budget->id;
        }
        app('log')->debug('Neither left or right have a budget, return NULL');

        // if all fails, return NULL.
        return null;
    }

    private function getTransactionCategory(Transaction $left, Transaction $right): ?int
    {
        app('log')->debug('Now in getTransactionCategory()');

        // try to get a category ID from the left transaction:
        /** @var null|Category $category */
        $category = $left->categories()->first();
        if (null !== $category) {
            app('log')->debug(sprintf('Return category #%d, from transaction #%d', $category->id, $left->id));

            return $category->id;
        }

        // try to get a category ID from the left transaction:
        /** @var null|Category $category */
        $category = $right->categories()->first();
        if (null !== $category) {
            app('log')->debug(sprintf('Return category #%d, from transaction #%d', $category->id, $category->id));

            return $category->id;
        }
        app('log')->debug('Neither left or right have a category, return NULL');

        // if all fails, return NULL.
        return null;
    }

    /**
     * Gives all journals without a group a group.
     */
    private function makeGroupsFromAll(): void
    {
        $orphanedJournals = $this->cliRepository->getJournalsWithoutGroup();
        $total            = count($orphanedJournals);
        if ($total > 0) {
            app('log')->debug(sprintf('Going to convert %d transaction journals. Please hold..', $total));
            $this->friendlyInfo(sprintf('Going to convert %d transaction journals. Please hold..', $total));

            /** @var array $array */
            foreach ($orphanedJournals as $array) {
                $this->giveGroup($array);
            }
        }
        if (0 === $total) {
            $this->friendlyPositive('No need to convert transaction journals.');
        }
    }

    private function giveGroup(array $array): void
    {
        $groupId = \DB::table('transaction_groups')->insertGetId(
            [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'title'      => null,
                'user_id'    => $array['user_id'],
            ]
        );
        \DB::table('transaction_journals')->where('id', $array['id'])->update(['transaction_group_id' => $groupId]);
        ++$this->count;
    }

    private function markAsMigrated(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
