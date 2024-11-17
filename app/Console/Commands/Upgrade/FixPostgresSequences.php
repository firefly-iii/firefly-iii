<?php

/*
 * FixPostgresSequences.php
 * Copyright (c) 2023 james@firefly-iii.org
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
use Illuminate\Console\Command;

/**
 * Class FixPostgresSequences
 */
class FixPostgresSequences extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Fixes issues with PostgreSQL sequences.';

    protected $signature   = 'firefly-iii:fix-pgsql-sequences';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ('pgsql' !== \DB::connection()->getName()) {
            return 0;
        }
        $this->friendlyLine('Going to verify PostgreSQL table sequences.');
        $tablesToCheck = ['2fa_tokens', 'account_meta', 'account_types', 'accounts', 'attachments', 'auto_budgets', 'available_budgets', 'bills', 'budget_limits', 'budget_transaction', 'budget_transaction_journal', 'budgets', 'categories', 'category_transaction', 'category_transaction_journal', 'configuration', 'currency_exchange_rates', 'failed_jobs', 'group_journals', 'jobs', 'journal_links', 'journal_meta', 'link_types', 'locations', 'migrations', 'notes', 'oauth_clients', 'oauth_personal_access_clients', 'object_groups', 'permissions', 'piggy_bank_events', 'piggy_bank_repetitions', 'piggy_banks', 'preferences', 'recurrences', 'recurrences_meta', 'recurrences_repetitions', 'recurrences_transactions', 'roles', 'rt_meta', 'rule_actions', 'rule_groups', 'rule_triggers', 'rules', 'tag_transaction_journal', 'tags', 'transaction_currencies', 'transaction_groups', 'transaction_journals', 'transaction_types', 'transactions', 'users', 'webhook_attempts', 'webhook_messages', 'webhooks'];

        foreach ($tablesToCheck as $tableToCheck) {
            $this->friendlyLine(sprintf('Checking the next id sequence for table "%s".', $tableToCheck));

            $highestId = \DB::table($tableToCheck)->select(\DB::raw('MAX(id)'))->first();
            $nextId    = \DB::table($tableToCheck)->select(\DB::raw(sprintf('nextval(\'%s_id_seq\')', $tableToCheck)))->first();
            if (null === $nextId) {
                $this->friendlyInfo(sprintf('nextval is NULL for table "%s", go to next table.', $tableToCheck));

                continue;
            }

            if ($nextId->nextval < $highestId->max) { // @phpstan-ignore-line
                \DB::select(sprintf('SELECT setval(\'%s_id_seq\', %d)', $tableToCheck, $highestId->max));
                $highestId = \DB::table($tableToCheck)->select(\DB::raw('MAX(id)'))->first();
                $nextId    = \DB::table($tableToCheck)->select(\DB::raw(sprintf('nextval(\'%s_id_seq\')', $tableToCheck)))->first();
                if ($nextId->nextval > $highestId->max) { // @phpstan-ignore-line
                    $this->friendlyInfo(sprintf('Table "%s" autoincrement corrected.', $tableToCheck));
                }
                if ($nextId->nextval <= $highestId->max) {
                    $this->friendlyWarning(sprintf('Arff! The nextval sequence is still all screwed up on table "%s".', $tableToCheck));
                }
            }
            if ($nextId->nextval >= $highestId->max) {
                $this->friendlyPositive(sprintf('Table "%s" autoincrement is correct.', $tableToCheck));
            }
        }

        return 0;
    }
}
