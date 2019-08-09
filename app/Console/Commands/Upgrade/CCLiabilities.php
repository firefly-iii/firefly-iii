<?php
/**
 * CCLiabilities.php
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

namespace FireflyIII\Console\Commands\Upgrade;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Class CCLiabilities
 */
class CCLiabilities extends Command
{


    public const CONFIG_NAME = '4780_cc_liabilities';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert old credit card liabilities.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:cc-liabilities {--F|force : Force the execution of this command.}';

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

        $ccType   = AccountType::where('type', AccountType::CREDITCARD)->first();
        $debtType = AccountType::where('type', AccountType::DEBT)->first();
        if (null === $ccType || null === $debtType) {
            $this->info('No incorrectly stored credit card liabilities.');

            return 0;
        }
        /** @var Collection $accounts */
        $accounts = Account::where('account_type_id', $ccType->id)->get();
        foreach ($accounts as $account) {
            $account->account_type_id = $debtType->id;
            $account->save();
            $this->line(sprintf('Converted credit card liability account "%s" (#%d) to generic debt liability.', $account->name, $account->id));
        }
        if ($accounts->count() > 0) {
            $this->info('Credit card liability types are no longer supported and have been converted to generic debts. See: http://bit.ly/FF3-credit-cards');
        }
        if (0 === $accounts->count()) {
            $this->info('No incorrectly stored credit card liabilities.');
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified credit card liabilities in %s seconds', $end));
        $this->markAsExecuted();

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