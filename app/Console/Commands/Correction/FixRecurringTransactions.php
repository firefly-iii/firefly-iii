<?php
/**
 * FixRecurringTransactions.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

/**
 * Class FixRecurringTransactions
 */
class FixRecurringTransactions extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes recurring transactions with the wrong transaction type.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-recurring-transactions';
    /** @var RecurringRepositoryInterface */
    private $recurringRepos;
    /** @var UserRepositoryInterface */
    private $userRepos;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start = microtime(true);
        $this->stupidLaravel();
        $this->correctTransactions();


        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Corrected recurring transactions %s seconds.', $end));

        return 0;
    }

    /**
     *
     */
    private function correctTransactions(): void
    {
        $users = $this->userRepos->all();
        /** @var User $user */
        foreach ($users as $user) {
            $this->recurringRepos->setUser($user);
            $recurrences = $this->recurringRepos->get();
            /** @var Recurrence $recurrence */
            foreach ($recurrences as $recurrence) {
                /** @var RecurrenceTransaction $transaction */
                foreach ($recurrence->recurrenceTransactions as $transaction) {
                    $source      = $transaction->sourceAccount;
                    $destination = $transaction->destinationAccount;
                    $type        = $recurrence->transactionType;
                    $link        = config(sprintf('firefly.account_to_transaction.%s.%s', $source->accountType->type, $destination->accountType->type));
                    if (null !== $link && strtolower($type->type) !== strtolower($link)) {
                        $this->warn(
                            sprintf('Recurring transaction #%d should be a "%s" but is a "%s" and will be corrected.', $recurrence->id, $link, $type->type)
                        );
                        $transactionType = TransactionType::whereType($link)->first();
                        if (null !== $transactionType) {
                            $recurrence->transaction_type_id = $transactionType->id;
                            $recurrence->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->recurringRepos = app(RecurringRepositoryInterface::class);
        $this->userRepos      = app(UserRepositoryInterface::class);
    }


}
