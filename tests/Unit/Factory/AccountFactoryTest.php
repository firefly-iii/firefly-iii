<?php
/**
 * AccountFactoryTest.php
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

namespace Tests\Unit\Factory;


use Carbon\Carbon;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\AccountType;
use Tests\TestCase;

/**
 * Class AccountFactoryTest
 */
class AccountFactoryTest extends TestCase
{
    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasic()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'  => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));
    }

    /**
     * Test creation of CC asset.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicCC()
    {

        $data = [
            'account_type_id'      => null,
            'accountType'          => 'asset',
            'iban'                 => null,
            'name'                 => 'Basic CC account #' . random_int(1, 1000),
            'virtualBalance'       => null,
            'active'               => true,
            'accountRole'          => 'ccAsset',
            'ccMonthlyPaymentDate' => '2018-01-01',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('ccAsset', $account->getMeta('accountRole'));
        $this->assertEquals('2018-01-01', $account->getMeta('ccMonthlyPaymentDate'));
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicExpense()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'expense',
            'iban'            => null,
            'name'            => 'Basic expense account #' . random_int(1, 1000),
            'virtualBalance'  => '1243',
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::EXPENSE, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('', $account->getMeta('accountRole'));
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicExpenseFullType()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'Expense account',
            'iban'            => null,
            'name'            => 'Basic expense account #' . random_int(1, 1000),
            'virtualBalance'  => '1243',
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::EXPENSE, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('', $account->getMeta('accountRole'));
    }

    /**
     * Submit IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicIB()
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'     => null,
            'active'             => true,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => '100',
            'openingBalanceDate' => new Carbon('2018-01-01'),
            'currency_id'        => 1,
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));

        // find opening balance:
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(100, floatval($account->transactions()->first()->amount));
    }

    /**
     * Submit empty (amount = 0) IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicIBZero()
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'     => null,
            'active'             => true,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => '0.0',
            'openingBalanceDate' => new Carbon('2018-01-01'),
            'currency_id'        => 1,
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));

        // find opening balance:
        $this->assertEquals(0, $account->transactions()->count());
    }

    /**
     * Add valid IBAN.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicIban()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => 'NL18RABO0326747238',
            'name'            => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'  => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('NL18RABO0326747238', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));
    }

    /**
     * Add invalid IBAN.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicInvalidIban()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => 'NL1XRABO032674X238',
            'name'            => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'  => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));
    }

    /**
     * Submit IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicNegativeIB()
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'     => null,
            'active'             => true,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => '-100',
            'openingBalanceDate' => new Carbon('2018-01-01'),
            'currency_id'        => 1,
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));

        // find opening balance:
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(-100, floatval($account->transactions()->first()->amount));
    }

    /**
     * Add some notes to asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicNotes()
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 1000),
            'virtualBalance'  => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
            'notes'           => 'Hello!',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
        $this->assertEquals('defaultAsset', $account->getMeta('accountRole'));
        $note = $account->notes()->first();
        $this->assertEquals('Hello!', $note->text);
    }

    /**
     * Should return existing account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     */
    public function testCreateExisting()
    {
        $existing = $this->user()->accounts()->where('account_type_id', 3)->first();
        $data     = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'name'            => $existing->name,
            'virtualBalance'  => null,
            'iban'            => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        $account = $factory->create($data);

        // assert stuff about account:
        $this->assertEquals($account->id, $existing->id);
    }

}
