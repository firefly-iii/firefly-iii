<?php
/*
 * ForceDecimalSize.php
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceDecimalSize extends Command
{
    use VerifiesAccessToken;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:force-decimal-size
                                {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command resizes DECIMAL columns in MySQL or PostgreSQL.';

    /**
     * Execute the console command.
     * @throws FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }

        $this->error('Running this command is dangerous and can cause data loss.');
        $this->error('Please do not continue.');
        $question = $this->confirm('Do you want to continue?');
        if (true === $question) {
            $user = $this->getUser();
            Log::channel('audit')->info(sprintf('User #%d ("%s") forced DECIMAL size.', $user->id, $user->email));
            $this->updateDecimals();
            return 0;
        }
        $this->line('Done!');
        return 0;
    }

    private function updateDecimals(): void
    {
        $this->info('Going to force the size of DECIMAL columns. Please hold.');
        $tables = [
            'accounts'                 => ['virtual_balance'],
            'auto_budgets'             => ['amount'],
            'available_budgets'        => ['amount'],
            'bills'                    => ['amount_min', 'amount_max'],
            'budget_limits'            => ['amount'],
            'currency_exchange_rates'  => ['rate', 'user_rate'],
            'limit_repetitions'        => ['amount'],
            'piggy_bank_events'        => ['amount'],
            'piggy_bank_repetitions'   => ['currentamount'],
            'piggy_banks'              => ['targetamount'],
            'recurrences_transactions' => ['amount', 'foreign_amount'],
            'transactions'             => ['amount', 'foreign_amount'],
        ];
        /**
         * @var string $name
         * @var array $fields
         */
        foreach($tables as $name => $fields) {
            /** @var string $field */
            foreach($fields as $field) {
                $this->line(sprintf('Updating table "%s", field "%s"...', $name, $field));
                $query = sprintf('ALTER TABLE %s CHANGE COLUMN %s %s DECIMAL(32, 12);', $name, $field, $field);
                DB::select($query);
                sleep(1);
            }
        }
    }
}
