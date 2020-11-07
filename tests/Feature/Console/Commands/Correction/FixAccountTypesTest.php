<?php
/**
 * FixAccountTypesTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Feature\Console\Commands\Correction;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Tests\TestCase;

/**
 * Class FixAccountTypesTest
 */
class FixAccountTypesTest extends TestCase
{
    /**
     * @covers \FireflyIII\Console\Commands\Correction\FixAccountTypes
     */
    public function testHandleUneven(): void
    {
        $source  = $this->getRandomDebt();
        $type    = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id,
                'amount'                 => '-10',
            ]
        );

        // assume there's nothing to fix.
        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(sprintf('Cannot inspect transaction journal #%d because it has 1 transaction(s) instead of 2.', $journal->id))
             ->assertExitCode(0);
        $one->forceDelete();
        $journal->forceDelete();
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\FixAccountTypes
     */
    public function testHandle(): void
    {
        // assume there's nothing to fix.
        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput('All account types are OK!')
             ->assertExitCode(0);
    }

    /**
     * Try to fix a withdrawal that goes from a loan to another loan.
     *
     * @covers \FireflyIII\Console\Commands\Correction\FixAccountTypes
     */
    public function testHandleWithdrawalLoanLoan(): void
    {
        $source      = $this->getRandomLoan();
        $destination = $this->getRandomLoan($source->id);
        $type        = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id,
                'amount'                 => '-10',
            ]
        );
        $two         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $destination->id,
                'amount'                 => '10',
            ]
        );


        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(sprintf('The source account of %s #%d cannot be of type "%s".', $type->type, $journal->id, 'Loan'))
             ->expectsOutput(sprintf('The destination account of %s #%d cannot be of type "%s".', $type->type, $journal->id, 'Loan'))
             ->expectsOutput('Acted on 1 transaction(s)!')
             ->assertExitCode(0);

        // since system cant handle this problem, dont look for changed transactions.
        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Transferring from an asset to a loan should be a withdrawal, not a transfer
     */
    public function testHandleTransferAssetLoan(): void
    {
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomLoan();
        $type        = TransactionType::where('type', TransactionType::TRANSFER)->first();
        $withdrawal  = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id,
                'amount'                 => '-10',
            ]
        );
        $two         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $destination->id,
                'amount'                 => '10',
            ]
        );

        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(sprintf('Converted transaction #%d from a transfer to a withdrawal.', $journal->id))
             ->expectsOutput('Acted on 1 transaction(s)!')
             ->assertExitCode(0);

        // verify the change has been made.
        $this->assertCount(1, TransactionJournal::where('id', $journal->id)->where('transaction_type_id', $withdrawal->id)->get());
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->where('transaction_type_id', $type->id)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Transferring from a loan to an asset should be a deposit, not a transfer
     */
    public function testHandleTransferLoanAsset(): void
    {
        $source      = $this->getRandomLoan();
        $destination = $this->getRandomAsset();
        $type        = TransactionType::where('type', TransactionType::TRANSFER)->first();
        $deposit     = TransactionType::where('type', TransactionType::DEPOSIT)->first();
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id,
                'amount'                 => '-10',
            ]
        );
        $two         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $destination->id,
                'amount'                 => '10',
            ]
        );

        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(sprintf('Converted transaction #%d from a transfer to a deposit.', $journal->id))
             ->expectsOutput('Acted on 1 transaction(s)!')
             ->assertExitCode(0);

        // verify the change has been made.
        $this->assertCount(1, TransactionJournal::where('id', $journal->id)->where('transaction_type_id', $deposit->id)->get());
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->where('transaction_type_id', $type->id)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Withdrawal with a revenue account as a destination must be converted.
     */
    public function testHandleWithdrawalAssetRevenue(): void
    {
        $source      = $this->getRandomAsset();
        $destination = $this->getRandomRevenue(); // is revenue account.
        $withdrawal  = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $withdrawal->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id,
                'amount'                 => '-10',
            ]
        );
        $two         = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $destination->id, // revenue cannot be destination.
                'amount'                 => '10',
            ]
        );

        // create expense account with the same name:
        $expense        = AccountType::where('type', AccountType::EXPENSE)->first();
        $newDestination = Account::create(
            [
                'name'            => $destination->name,
                'account_type_id' => $expense->id,
                'user_id'         => 1,
            ]
        );

        // asset we find bad destination.
        $this->assertCount(0, Transaction::where('id', $two->id)->where('account_id', $newDestination->id)->get());
        $this->assertCount(1, Transaction::where('id', $two->id)->where('account_id', $destination->id)->get());

        // Transaction journal #137, destination account changed from #1 ("Checking Account") to #29 ("Land lord").
        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(
                 sprintf(
                     'Transaction journal #%d, destination account changed from #%d ("%s") to #%d ("%s").',
                     $journal->id,
                     $destination->id, $destination->name,
                     $newDestination->id, $newDestination->name
                 )
             )
             ->expectsOutput('Acted on 1 transaction(s)!')
             ->assertExitCode(0);

        // verify the change has been made
        $this->assertCount(1, Transaction::where('id', $two->id)->where('account_id', $newDestination->id)->get());
        $this->assertCount(0, Transaction::where('id', $two->id)->where('account_id', $destination->id)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Deposit with an expense account as a source instead of a revenue account must be converted.
     */
    public function testHandleDepositAssetExpense(): void
    {
        $source      = $this->getRandomExpense(); // expense account
        //$newSource   = $this->getRandomRevenue();
        $destination = $this->getRandomAsset();

        $deposit = TransactionType::where('type', TransactionType::DEPOSIT)->first();
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $deposit->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $source->id, // expense account cannot be source.
                'amount'                 => '-10',
            ]
        );
        $two     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $destination->id,
                'amount'                 => '10',
            ]
        );
        // create revenue account with the same name:
        $revenue      = AccountType::where('type', AccountType::REVENUE)->first();
        $newSource = Account::create(
            [
                'name'            => $source->name,
                'account_type_id' => $revenue->id,
                'user_id'         => 1,
            ]
        );

        $this->assertCount(0, Transaction::where('id', $one->id)->where('account_id', $newSource->id)->get());
        $this->assertCount(1, Transaction::where('id', $one->id)->where('account_id', $source->id)->get());


        // Transaction journal #137, destination account changed from #1 ("Checking Account") to #29 ("Land lord").
        $this->artisan('firefly-iii:fix-account-types')
             ->expectsOutput(
                 sprintf(
                     'Transaction journal #%d, source account changed from #%d ("%s") to #%d ("%s").',
                     $journal->id,
                     $destination->id, $destination->name,
                     $newSource->id, $newSource->name
                 )
             )
             ->expectsOutput('Acted on 1 transaction(s)!')
             ->assertExitCode(0);

        $this->assertCount(1, Transaction::where('id', $one->id)->where('account_id', $newSource->id)->get());
        $this->assertCount(0, Transaction::where('id', $one->id)->where('account_id', $source->id)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }
}
