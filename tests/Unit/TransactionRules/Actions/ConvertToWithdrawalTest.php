<?php
/**
 * ConvertToWithdrawalTest.php
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

namespace Tests\Unit\TransactionRules\Actions;


use Exception;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\ConvertToWithdrawal;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToWithdrawalTest
 */
class ConvertToWithdrawalTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Convert a deposit to a withdrawal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToWithdrawal
     */
    public function testActDeposit()
    {
        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('description', 'Deposit for ConvertToWithdrawalTest.')->first();

        // new expense account:
        $name = sprintf('Random expense #%d', $this->randomInt());

        // original destination:
        $destination = Account::where('name','Checking Account')->first();

        // journal is a deposit:
        $this->assertEquals(TransactionType::DEPOSIT, $deposit->transactionType->type);

        // array:
        $array = [
            'user_id'                => $this->user()->id,
            'transaction_journal_id' => $deposit->id,
            'transaction_type_type'  => $deposit->transactionType->type,
            'source_account_name'    => 'Boss',
            'destination_account_id' => $destination->id
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToWithdrawal($ruleAction);
        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a withdrawal.
        $deposit->refresh();
        $this->assertEquals(TransactionType::WITHDRAWAL, $deposit->transactionType->type);
    }

    /**
     * Convert a transfer to a withdrawal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToWithdrawal
     */
    public function testActTransfer()
    {
        /** @var TransactionJournal $transfer */
        $transfer = $this->user()->transactionJournals()->where('description', 'Transfer for ConvertToWithdrawalTest.')->first();

        // new expense account:
        $name = sprintf('Random new expense #%d', $this->randomInt());

        $destination = Account::whereName('Checking Account')->first();

        // journal is a transfer:
        $this->assertEquals(TransactionType::TRANSFER, $transfer->transactionType->type);

        // array:
        $array = [
            'user_id'                => $this->user()->id,
            'transaction_journal_id' => $transfer->id,
            'transaction_type_type'  => $transfer->transactionType->type,
            'destination_account_name' => $destination->name,
            //'source_account_name'    => 'Boss',
            //'destination_account_id' => $destination->id
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToWithdrawal($ruleAction);
        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a withdrawal.
        $transfer->refresh();
        $this->assertEquals(TransactionType::WITHDRAWAL, $transfer->transactionType->type);
    }


}
