<?php

/*
 * FixIbans.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use Illuminate\Console\Command;

/**
 * Class FixIbans
 */
class FixIbans extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes spaces from IBANs';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-ibans';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $accounts = Account::whereNotNull('iban')->get();
        /** @var Account $account */
        foreach ($accounts as $account) {
            $iban = $account->iban;
            if (str_contains($iban, ' ')) {

                $iban = app('steam')->filterSpaces((string) $account->iban);
                if ('' !== $iban) {
                    $account->iban = $iban;
                    $account->save();
                    $this->line(sprintf('Removed spaces from IBAN of account #%d', $account->id));
                }
            }
        }

        return 0;
    }
}
