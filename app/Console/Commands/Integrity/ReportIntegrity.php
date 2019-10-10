<?php
/**
 * ReportIntegrity.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Integrity;


use Artisan;
use Illuminate\Console\Command;
use Schema;

/**
 * Class ReportIntegrity
 * @codeCoverageIgnore
 */
class ReportIntegrity extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will report on the integrity of your database.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:report-integrity';

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
            'firefly-iii:report-empty-objects',
            'firefly-iii:report-sum',
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
