<?php
/**
 * AccountDestroyServiceClass.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Services\Internal\Destroy;

use FireflyIII\Models\Account;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Transaction;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\RecurrenceDestroyService;
use Log;
use Tests\TestCase;

/**
 * Class AccountDestroyServiceTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccountDestroyServiceTest extends TestCase
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
     * @covers \FireflyIII\Services\Internal\Destroy\AccountDestroyService
     */
    public function testDestroyBasic(): void
    {
        $this->mock(RecurrenceDestroyService::class);
        $this->mock(JournalDestroyService::class);
        $account = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, null);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id, 'deleted_at' => null]);
    }


    /**
     * @covers \FireflyIII\Services\Internal\Destroy\AccountDestroyService
     */
    public function testDestroyWithRecurrence(): void
    {
        $recService = $this->mock(RecurrenceDestroyService::class);
        $this->mock(JournalDestroyService::class);
        $account = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );

        $recurrence  = $this->getRandomRecurrence();
        $transaction = RecurrenceTransaction::create(
            [
                'recurrence_id'           => $recurrence->id,
                'transaction_currency_id' => $this->getEuro()->id,
                'source_id'               => $account->id,
                'destination_id'          => $account->id,
                'amount'                  => 10,
                'description'             => 'Hello',
            ]
        );

        $recService->shouldReceive('destroyById')->once()
                   ->withAnyArgs([$transaction->id]);

        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, null);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id, 'deleted_at' => null]);

        $transaction->forceDelete();
    }

    /**
     * @covers \FireflyIII\Services\Internal\Destroy\AccountDestroyService
     */
    public function testDestroyDontMove(): void
    {
        $this->mock(RecurrenceDestroyService::class);
        $this->mock(JournalDestroyService::class);
        // create objects:
        $account = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        Transaction::create(['account_id' => $account->id, 'transaction_journal_id' => 1, 'amount' => 10, 'transaction_currency_id' => 1]);

        // mock delete service:
        $service = $this->mock(JournalDestroyService::class);
        $service->shouldReceive('destroy')->once();

        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, null);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id, 'deleted_at' => null]);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Destroy\AccountDestroyService
     */
    public function testDestroyMove(): void
    {
        $this->mock(RecurrenceDestroyService::class);
        $this->mock(JournalDestroyService::class);
        $account     = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $move        = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $transaction = Transaction::create(['account_id' => $account->id, 'transaction_journal_id' => 1, 'amount' => 10, 'transaction_currency_id' => 1]);
        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, $move);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('transactions', ['account_id' => $account->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'account_id' => $move->id]);
    }

}
