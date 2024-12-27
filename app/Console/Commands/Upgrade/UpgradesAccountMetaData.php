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

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountMeta;
use Illuminate\Console\Command;

class UpgradesAccountMetaData extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_rename_account_meta';

    protected $description          = 'Rename account meta-data to new format.';

    protected $signature            = 'upgrade:480-account-meta {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

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
            AccountMeta::where('name', $new)->where('data', '""')->delete();
        }

        $this->markAsExecuted();

        if (0 === $count) {
            $this->friendlyPositive('All account meta is OK.');
        }
        if (0 !== $count) {
            $this->friendlyInfo(sprintf('Renamed %d account meta entries (entry).', $count));
        }

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
