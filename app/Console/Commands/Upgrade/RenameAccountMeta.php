<?php
/**
 * RenameAccountMeta.php
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

use FireflyIII\Models\AccountMeta;
use Illuminate\Console\Command;

/**
 * Class RenameAccountMeta
 */
class RenameAccountMeta extends Command
{
    public const CONFIG_NAME = '480_rename_account_meta';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename account meta-data to new format.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:rename-account-meta {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd
        $array = [
            'accountRole'          => 'account_role',
            'ccType'               => 'cc_type',
            'accountNumber'        => 'account_number',
            'ccMonthlyPaymentDate' => 'cc_monthly_payment_date',
        ];
        $count = 0;

        /**
         * @var string $old
         * @var string $new
         */
        foreach ($array as $old => $new) {
            $count += AccountMeta::where('name', $old)->update(['name' => $new]);

            // delete empty entries while we're at it.
            AccountMeta::where('name', $new)->where('data','""')->delete();
        }

        $this->markAsExecuted();

        if (0 === $count) {
            $this->line('All account meta is OK.');
        }
        if (0 !== $count) {
            $this->line(sprintf('Renamed %d account meta entries (entry).', $count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Fixed account meta data in %s seconds.', $end));

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
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
