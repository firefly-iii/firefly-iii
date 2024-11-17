<?php
/**
 * TransactionIdentifier.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Class TransactionIdentifier
 */
class TransactionIdentifier extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_transaction_identifier';
    protected $description          = 'Fixes transaction identifiers.';
    protected $signature            = 'firefly-iii:transaction-identifiers {--F|force : Force the execution of this command.}';
    private JournalCLIRepositoryInterface $cliRepository;
    private int                           $count;

    /**
     * This method gives all transactions which are part of a split journal (so more than 2) a sort of "order" so they
     * are easier to easier to match to their counterpart. When a journal is split, it has two or three transactions:
     * -3, -4 and -5 for example.
     *
     * In the database this is reflected as 6 transactions: -3/+3, -4/+4, -5/+5.
     *
     * When either of these are the same amount, FF3 can't keep them apart: +3/-3, +3/-3, +3/-3. This happens more
     * often than you would think. So each set gets a number (1,2,3) to keep them apart.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->stupidLaravel();

        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        // if table does not exist, return false
        if (!\Schema::hasTable('transaction_journals')) {
            return 0;
        }

        $journals = $this->cliRepository->getSplitJournals();

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->updateJournalIdentifiers($journal);
        }

        if (0 === $this->count) {
            $this->friendlyPositive('All split journal transaction identifiers are OK.');
        }
        if (0 !== $this->count) {
            $this->friendlyInfo(sprintf('Fixed %d split journal transaction identifier(s).', $this->count));
        }

        $this->markAsExecuted();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->cliRepository = app(JournalCLIRepositoryInterface::class);
        $this->count         = 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    /**
     * Grab all positive transactions from this journal that are not deleted. for each one, grab the negative opposing
     * one which has 0 as an identifier and give it the same identifier.
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
                $exclude[]               = $transaction->id;
                $exclude[]               = $opposing->id;
                ++$this->count;
            }
            ++$identifier;
        }
    }

    private function findOpposing(Transaction $transaction, array $exclude): ?Transaction
    {
        // find opposing:
        $amount = bcmul($transaction->amount, '-1');

        try {
            /** @var Transaction $opposing */
            $opposing = Transaction::where('transaction_journal_id', $transaction->transaction_journal_id)
                ->where('amount', $amount)->where('identifier', '=', 0)
                ->whereNotIn('id', $exclude)
                ->first()
            ;
        } catch (QueryException $e) {
            app('log')->error($e->getMessage());
            $this->friendlyError('Firefly III could not find the "identifier" field in the "transactions" table.');
            $this->friendlyError(sprintf('This field is required for Firefly III version %s to run.', config('firefly.version')));
            $this->friendlyError('Please run "php artisan migrate --force" to add this field to the table.');
            $this->friendlyError('Then, run "php artisan firefly:upgrade-database" to try again.');

            return null;
        }

        return $opposing;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
