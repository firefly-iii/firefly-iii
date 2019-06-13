<?php
/**
 * UpgradeDatabase.php
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

set_time_limit(0);

use Artisan;
use Illuminate\Console\Command;

/**
 * Class UpgradeDatabase
 * @codeCoverageIgnore
 */
class UpgradeDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrades the database to the latest version.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:upgrade-database {--F|force : Force all upgrades.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $commands = [
            // there are 11 upgrade commands.
            'firefly-iii:transaction-identifiers',
            'firefly-iii:account-currencies',
            'firefly-iii:transfer-currencies',
            'firefly-iii:other-currencies',
            'firefly-iii:migrate-notes',
            'firefly-iii:migrate-attachments',
            'firefly-iii:bills-to-rules',
            'firefly-iii:bl-currency',
            'firefly-iii:cc-liabilities',
            'firefly-iii:migrate-to-groups',
            'firefly-iii:back-to-journals',
        ];
        $args     = [];
        if ($this->option('force')) {
            $args = ['--force' => true];
        }
        foreach ($commands as $command) {
            $this->line(sprintf('Now executing %s', $command));
            Artisan::call($command, $args);
            $result = Artisan::output();
            echo $result;
        }

        return 0;
    }
}
