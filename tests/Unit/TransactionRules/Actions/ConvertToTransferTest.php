<?php
/**
 * ConvertToTransferTest.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\ConvertToTransfer;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToTransferTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConvertToTransferTest extends TestCase
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
     * Convert a deposit to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActDeposit(): void
    {
        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('description', 'Deposit for ConvertToTransferTest.')->first();

        // get new source account (replaces the revenue account):
        $newSource   = Account::whereName('Savings Account')->first();
        $destination = Account::whereName('Checking Account')->first();
        // journal is a withdrawal:
        $this->assertEquals(TransactionType::DEPOSIT, $deposit->transactionType->type);

        // make the required array:
        $array = [
            'transaction_journal_id' => $deposit->id,
            'transaction_type_type'  => $deposit->transactionType->type,
            'user_id'                => 1,
            'destination_account_id' => $destination->id,
        ];

        // fire the action:
        $rule                     = new Rule;
        $rule->title              = 'OK';
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Savings Account';
        $ruleAction->rule         = $rule;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $deposit->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $deposit->transactionType->type);
    }

    /**
     * Convert a withdrawal to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActWithdrawal(): void
    {
        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('description', 'Withdrawal for ConvertToTransferTest.')->first();

        // new asset to link to destination of withdrawal:
        $newDestination = Account::whereName('Savings Account')->first();
        $source         = Account::whereName('Checking Account')->first();
        // array with necessary data:
        $array = [
            'transaction_journal_id' => $withdrawal->id,
            'transaction_type_type'  => $withdrawal->transactionType->type,
            'user_id'                => 1,
            'source_account_id'      => $source->id,
        ];

        // fire the action:
        $rule                     = new Rule;
        $rule->title              = 'OK';
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newDestination->name;
        $ruleAction->rule         = $rule;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $withdrawal->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $withdrawal->transactionType->type);
    }


}
