<?php
declare(strict_types=1);
/**
 * TransactionIdentifier.php
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

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Log;
use Schema;

/**
 * Class TransactionIdentifier
 */
class TransactionIdentifier extends Command
{
    public const CONFIG_NAME = '4780_transaction_identifier';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes transaction identifiers.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:transaction-identifiers {--F|force : Force the execution of this command.}';

    /** @var JournalRepositoryInterface */
    private $journalRepository;

    /** @var JournalCLIRepositoryInterface */
    private $cliRepository;

    /** @var int */
    private $count;

    /**
     * This method gives all transactions which are part of a split journal (so more than 2) a sort of "order" so they are easier
     * to easier to match to their counterpart. When a journal is split, it has two or three transactions: -3, -4 and -5 for example.
     *
     * In the database this is reflected as 6 transactions: -3/+3, -4/+4, -5/+5.
     *
     * When either of these are the same amount, FF3 can't keep them apart: +3/-3, +3/-3, +3/-3. This happens more often than you would
     * think. So each set gets a number (1,2,3) to keep them apart.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        // if table does not exist, return false
        if (!Schema::hasTable('transaction_journals')) {
            return 0;
        }
        // @codeCoverageIgnoreEnd
        $journals = $this->cliRepository->getSplitJournals();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->updateJournalIdentifiers($journal);
        }

        if (0 === $this->count) {
            $this->line('All split journal transaction identifiers are correct.');
        }
        if (0 !== $this->count) {
            $this->line(sprintf('Fixed %d split journal transaction identifier(s).', $this->count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed transaction identifiers in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->journalRepository = app(JournalRepositoryInterface::class);
        $this->cliRepository     = app(JournalCLIRepositoryInterface::class);
        $this->count             = 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    /**
     * Grab all positive transactions from this journal that are not deleted. for each one, grab the negative opposing one
     * which has 0 as an identifier and give it the same identifier.
     *
     * @param TransactionJournal $transactionJournal
     */
    private function updateJournalIdentifiers(TransactionJournal $transactionJournal): void
    {
        $identifier   = 0;
        $exclude      = []; // transactions already processed.
        $transactions = $transactionJournal->transactions()->where('amount', '>', 0)->get();

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $opposing = $this->findOpposing($transaction, $exclude);
            if (null !== $opposing) {
                // give both a new identifier:
                $transaction->identifier = $identifier;
                $opposing->identifier    = $identifier;
                $transaction->save();
                $opposing->save();
                $exclude[] = $transaction->id;
                $exclude[] = $opposing->id;
                $this->count++;
            }
            ++$identifier;
        }

    }

    /**
     * @param Transaction $transaction
     * @param array $exclude
     * @return Transaction|null
     */
    private function findOpposing(Transaction $transaction, array $exclude): ?Transaction
    {
        // find opposing:
        $amount = bcmul((string)$transaction->amount, '-1');

        try {
            /** @var Transaction $opposing */
            $opposing = Transaction::where('transaction_journal_id', $transaction->transaction_journal_id)
                                   ->where('amount', $amount)->where('identifier', '=', 0)
                                   ->whereNotIn('id', $exclude)
                                   ->first();
            // @codeCoverageIgnoreStart
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            $this->error('Firefly III could not find the "identifier" field in the "transactions" table.');
            $this->error(sprintf('This field is required for Firefly III version %s to run.', config('firefly.version')));
            $this->error('Please run "php artisan migrate" to add this field to the table.');
            $this->info('Then, run "php artisan firefly:upgrade-database" to try again.');

            return null;
        }

        // @codeCoverageIgnoreEnd

        return $opposing;
    }
}
