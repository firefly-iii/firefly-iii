<?php
/**
 * CorrectDatabase.php
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

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;


use Artisan;
use Illuminate\Console\Command;
use Schema;

/**
 * Class CorrectDatabase
 * @codeCoverageIgnore
 */
class CorrectDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will correct the integrity of your database, if necessary.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:correct-database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // if table does not exist, return false
        if (!Schema::hasTable('users')) {
            return 1;
        }
        $commands = [
            'firefly-iii:fix-piggies',
            'firefly-iii:create-link-types',
            'firefly-iii:create-access-tokens',
            'firefly-iii:remove-bills',
            'firefly-iii:enable-currencies',
            'firefly-iii:fix-transfer-budgets',
            'firefly-iii:fix-uneven-amount',
            'firefly-iii:delete-zero-amount',
            'firefly-iii:delete-orphaned-transactions',
            'firefly-iii:delete-empty-journals',
            'firefly-iii:delete-empty-groups',
            'firefly-iii:fix-account-types',
            'firefly-iii:rename-meta-fields',
            'firefly-iii:fix-ob-currencies'
        ];
        foreach ($commands as $command) {
            $this->line(sprintf('Now executing %s', $command));
            Artisan::call($command);
            $result = Artisan::output();
            echo $result;
        }

        return 0;
    }
}
