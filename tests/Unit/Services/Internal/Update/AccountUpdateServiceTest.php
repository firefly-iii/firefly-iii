<?php
/**
 * AccountUpdateServiceTest.php
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

namespace Tests\Unit\Services\Internal\Update;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use Log;
use Tests\TestCase;

/**
 * Class AccountUpdateServiceTest
 */
class AccountUpdateServiceTest extends TestCase
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
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testDeleteExistingIB(): void
    {
        $group         = $this->getRandomWithdrawalGroup();
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $destroySerice = $this->mock(TransactionGroupDestroyService::class);
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn($group);
        $destroySerice->shouldReceive('destroy')->atLeast()->once();
        $account = $this->getRandomAsset();

        $data = [
            'name'            => 'Some new name #' . $this->randomInt(),
            'active'          => true,
            'virtual_balance' => '0',
            'iban'            => null,
            'account_role'    => 'defaultAsset',
            'notes'           => 'Hello',
            'currency_id'     => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateBasic(): void
    {
        /** @var Account $account */
        $account = $this->user()->accounts()->first();
        $data    = [
            'name'            => 'Some new name #' . $this->randomInt(),
            'active'          => true,
            'virtual_balance' => '0',
            'iban'            => null,
            'account_role'    => 'defaultAsset',
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateBasicEmptyNote(): void
    {
        /** @var Account $account */
        $account = $this->user()->accounts()->first();
        $data    = [
            'name'            => 'Some new name #' . $this->randomInt(),
            'active'          => true,
            'virtual_balance' => '0',
            'iban'            => null,
            'account_role'    => 'defaultAsset',
            'notes'           => '',
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(0, $account->notes()->count());
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateBasicExistingNote(): void
    {
        /** @var Account $account */
        $account = $this->user()->accounts()->first();
        $note    = new Note;
        $note->noteable()->associate($account);
        $note->text = 'Hi there';
        $note->save();

        $data = [
            'name'            => 'Some new name #' . $this->randomInt(),
            'active'          => true,
            'virtual_balance' => '0',
            'iban'            => null,
            'account_role'    => 'defaultAsset',
            'notes'           => '',
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(0, $account->notes()->count());
    }


    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateExistingIB(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $destroySerice = $this->mock(TransactionGroupDestroyService::class);
        $group         = $this->getRandomWithdrawalGroup();

        // make sure one transaction has the account as the asset.
        $journal = $group->transactionJournals()->first();
        $account = $journal->transactions()->first()->account;


        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn($group);
        $data = [
            'name'                 => 'Some new name #' . $this->randomInt(),
            'active'               => true,
            'virtual_balance'      => '0',
            'iban'                 => null,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '105',
            'opening_balance_date' => new Carbon('2018-01-01'),
            'notes'                => 'Hello',
            'currency_id'          => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateExistingIBZero(): void
    {

        $deleteService = $this->mock(JournalDestroyService::class);
        //$deleteService->shouldReceive('destroy')->once();

        /** @var Account $account */
        $account  = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . $this->randomInt(),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $opposing = $this->user()->accounts()->first();
        $journal  = TransactionJournal::create(
            ['user_id' => $this->user()->id, 'transaction_type_id' => 4, 'transaction_currency_id' => 1, 'description' => 'IB',
             'date'    => '2018-01-01', 'completed' => true, 'tag_count' => 0,
            ]
        );
        // transactions:
        Transaction::create(
            ['account_id'              => $account->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '100', 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $opposing->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '-100', 'identifier' => 0,]
        );


        $data = [
            'name'                 => 'Some new name #' . $this->randomInt(),
            'active'               => true,
            'virtual_balance'      => '0',
            'iban'                 => null,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '0',
            'opening_balance_date' => new Carbon('2018-01-01'),
            'notes'                => 'Hello',
            'currency_id'          => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(100, $account->transactions()->first()->amount);
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

}
