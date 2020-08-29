<?php
/**
 * ConvertToDepositTest.php
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
use FireflyIII\TransactionRules\Actions\ConvertToDeposit;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToDepositTest
 */
class ConvertToDepositTest extends TestCase
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
     * Convert a transfer to a deposit.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToDeposit
     */
    public function testActTransfer(): void
    {
        /** @var TransactionJournal $transfer */
        $transfer = $this->user()->transactionJournals()->where('description', 'Transfer for convertToDeposit.')->first();
        $name     = sprintf('Random revenue #%d', $this->randomInt());

        // journal is a transfer:
        $this->assertEquals(TransactionType::TRANSFER, $transfer->transactionType->type);

        // make array for action:
        $array = [
            'transaction_journal_id' => $transfer->id,
            'transaction_type_type'  => $transfer->transactionType->type,
            'user_id'                => $this->user()->id,
            'source_account_name'    => 'Checking Account',
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToDeposit($ruleAction);
        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);
        // get journal:
        $transfer->refresh();
        $this->assertEquals(TransactionType::DEPOSIT, $transfer->transactionType->type);
    }

    /**
     * Convert a withdrawal to a deposit.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToDeposit
     */
    public function testActWithdrawal(): void
    {
        /** @var TransactionJournal $withdrawal */
        $withdrawal = $this->user()->transactionJournals()->where('description', 'Withdrawal for convertToDeposit.')->first();
        $name       = sprintf('Random revenue #%d', $this->randomInt());

        // journal is a withdrawal:
        $this->assertEquals(TransactionType::WITHDRAWAL, $withdrawal->transactionType->type);

        // quick DB search for original source:
        $source = Account::where('name', 'Checking Account')->first();

        // make array for action:
        $array = [
            'transaction_journal_id'   => $withdrawal->id,
            'transaction_type_type'    => $withdrawal->transactionType->type,
            'user_id'                  => $this->user()->id,
            'destination_account_name' => 'SuperMarket',
            'source_account_id'        => $source->id,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToDeposit($ruleAction);
        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);
        // get journal:
        $withdrawal->refresh();
        $this->assertEquals(TransactionType::DEPOSIT, $withdrawal->transactionType->type);
    }
}
