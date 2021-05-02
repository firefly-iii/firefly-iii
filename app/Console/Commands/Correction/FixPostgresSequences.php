<?php
declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use DB;
use Illuminate\Console\Command;

/**
 * Class FixPostgresSequences
 */
class FixPostgresSequences extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes issues with PostgreSQL sequences.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-pgsql-sequences';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {

        if (DB::connection()->getName() !== 'pgsql') {
            $this->info('Command executed successfully.');

            return 0;
        }
        $this->line('Going to verify PostgreSQL table sequences.');
        $tablesToCheck = [
            '2fa_tokens',
            'account_meta',
            'account_types',
            'accounts',
            'attachments',
            'auto_budgets',
            'available_budgets',
            'bills',
            'budget_limits',
            'budget_transaction',
            'budget_transaction_journal',
            'budgets',
            'categories',
            'category_transaction',
            'category_transaction_journal',
            'configuration',
            'currency_exchange_rates',
            'export_jobs',
            'failed_jobs',
            'group_journals',
            'import_jobs',
            'jobs',
            'journal_links',
            'journal_meta',
            'limit_repetitions',
            'link_types',
            'locations',
            'migrations',
            'notes',
            'oauth_clients',
            'oauth_personal_access_clients',
            'object_groups',
            'permissions',
            'piggy_bank_events',
            'piggy_bank_repetitions',
            'piggy_banks',
            'preferences',
            'recurrences',
            'recurrences_meta',
            'recurrences_repetitions',
            'recurrences_transactions',
            'roles',
            'rt_meta',
            'rule_actions',
            'rule_groups',
            'rule_triggers',
            'rules',
            'tag_transaction_journal',
            'tags',
            'telemetry',
            'transaction_currencies',
            'transaction_groups',
            'transaction_journals',
            'transaction_types',
            'transactions',
            'users',
            'webhook_attempts',
            'webhook_messages',
            'webhooks',
        ];

        foreach ($tablesToCheck as $tableToCheck) {
            $this->info(sprintf('Checking the next id sequence for table "%s".', $tableToCheck));

            $highestId = DB::table($tableToCheck)->select(DB::raw('MAX(id)'))->first();
            $nextId    = DB::table($tableToCheck)->select(DB::raw(sprintf('nextval(\'%s_id_seq\')', $tableToCheck)))->first();
            if(null === $nextId) {
                $this->line(sprintf('nextval is NULL for table "%s", go to next table.', $tableToCheck));
                continue;
            }

            if ($nextId->nextval < $highestId->max) {
                DB::select(sprintf('SELECT setval(\'%s_id_seq\', %d)', $tableToCheck, $highestId->max));
                $highestId = DB::table($tableToCheck)->select(DB::raw('MAX(id)'))->first();
                $nextId    = DB::table($tableToCheck)->select(DB::raw(sprintf('nextval(\'%s_id_seq\')', $tableToCheck)))->first();
                if ($nextId->nextval > $highestId->max) {
                    $this->info(sprintf('Table "%s" autoincrement corrected.', $tableToCheck));
                }
                if ($nextId->nextval <= $highestId->max) {
                    $this->warn(sprintf('Arff! The nextval sequence is still all screwed up on table "%s".', $tableToCheck));
                }
            }
            if ($nextId->nextval >= $highestId->max) {
                $this->info(sprintf('Table "%s" autoincrement is correct.', $tableToCheck));
            }
        }


        return 0;
    }
}
