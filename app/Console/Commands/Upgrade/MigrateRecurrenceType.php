<?php

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Class MigrateRecurrenceType
 */
class MigrateRecurrenceType extends Command
{
    public const CONFIG_NAME = '550_migrate_recurrence_type';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate transaction type of recurring transaction.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:migrate-recurrence-type {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        $this->migrateTypes();

        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Update recurring transaction types in %s seconds.', $end));

        return 0;
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
    private function migrateTypes(): void
    {
        $set = Recurrence::get();
        /** @var Recurrence $recurrence */
        foreach ($set as $recurrence) {
            if ($recurrence->transactionType->type !== TransactionType::INVALID) {
                $this->migrateRecurrence($recurrence);
            }
        }
    }

    private function migrateRecurrence(Recurrence $recurrence): void
    {
        $originalType                    = (int)$recurrence->transaction_type_id;
        $newType                         = $this->getInvalidType();
        $recurrence->transaction_type_id = $newType->id;
        $recurrence->save();
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions as $transaction) {
            $transaction->transaction_type_id = $originalType;
            $transaction->save();
        }
        $this->line(sprintf('Updated recurrence #%d to new transaction type model.', $recurrence->id));
    }

    /**
     *
     */
    private function getInvalidType(): TransactionType
    {
        return TransactionType::whereType(TransactionType::INVALID)->firstOrCreate(['type' => TransactionType::INVALID]);
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
