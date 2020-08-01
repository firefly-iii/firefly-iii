<?php
/**
 * AccountFactoryTest.php
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

namespace Tests\Unit\Factory;


use Amount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class AccountFactoryTest
 */
class AccountFactoryTest extends TestCase
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
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateAsset(): void
    {
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals(0, $account->order);
        $this->assertNull($account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Submit invalid IBAN, so assume NULL on final result.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateInvalidIBAN(): void
    {
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => 'IAMINVALID',
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertTrue($account->active);
        $this->assertEquals(0, $account->order);
        $this->assertNull($account->virtual_balance);
        $this->assertNull($account->iban);

        $account->forceDelete();
    }

    /**
     * Submit invalid IBAN, so assume NULL on final result.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateValidIBAN(): void
    {
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => 'NL83ABNA8548609842', // fake IBAN, ABN AMRO IBAN's are always "ABNA0".
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertTrue($account->active);
        $this->assertEquals(0, $account->order);
        $this->assertNull($account->virtual_balance);
        $this->assertEquals($data['iban'], $account->iban);

        $account->forceDelete();
    }

    /**
     * Create asset, include opening balance.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateAssetOpeningBalance(): void
    {
        $data = [
            'account_type_id'      => null,
            'account_type'         => 'asset',
            'iban'                 => null,
            'name'                 => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance'      => null,
            'active'               => true,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '1234.56',
            'opening_balance_date' => today(),
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals(0, $account->order);
        $this->assertNull($account->virtual_balance);
        $this->assertCount(1, $account->transactions()->get());

        $account->forceDelete();
    }


    /**
     * Create expense account.
     *
     * - Virtual balance must become NULL despite being set.
     * - Account type is found by ID
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateExpense(): void
    {
        $expense = AccountType::where('type', AccountType::EXPENSE)->first();
        $data    = [
            'account_type_id' => $expense->id ?? null,
            'iban'            => null,
            'name'            => sprintf('Basic expense account #%d', $this->randomInt()),
            'virtual_balance' => '1234.56',
            'active'          => true,
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::EXPENSE, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals(0, $account->order);
        $this->assertNull($account->virtual_balance);

        $account->forceDelete();
    }


    /**
     * Unknown type.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testCreateErrorType(): void
    {
        // mock repositories
        $data = [
            'account_type_id' => null,
            'account_type'    => 'bad-type',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertEquals($e->getMessage(), 'AccountFactory::create() was unable to find account type #0 ("bad-type").');

            return;
        }
        $this->assertTrue(false, 'Should not reach here.');
    }

    /**
     * Find expense account we know doesn't exist.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     * @covers \FireflyIII\Services\Internal\Support\LocationServiceTrait
     */
    public function testFindOrCreate(): void
    {
        $name = sprintf('Basic account #%d', $this->randomInt());
        $type = AccountType::EXPENSE;

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->findOrCreate($name, $type);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
            return;
        }
        $this->assertEquals($name, $account->name);
        $this->assertEquals($type, $account->accountType->type);

    }
}
