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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use Log;
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
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasic(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        // get the currency ID:
        /** @var AccountMeta $meta */
        $currencyId = $account->accountMeta()->where('name', 'currency_id')->first();
        $this->assertNotNull($currencyId);
        $this->assertEquals('1', $currencyId->data);
    }

    /**
     * Test creation of CC asset.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicCC(): void
    {

        $data = [
            'account_type_id'      => null,
            'accountType'          => 'asset',
            'iban'                 => null,
            'name'                 => 'Basic CC account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('ccAsset', $meta->data);

        // get the date:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'ccMonthlyPaymentDate')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('2018-01-01', $meta->data);
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicEmptyVb(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
            'virtualBalance'  => '',
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicExpense(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'expense',
            'iban'            => null,
            'name'            => 'Basic expense account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNull($meta);
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicExpenseFullType(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'Expense account',
            'iban'            => null,
            'name'            => 'Basic expense account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNull($meta);
    }

    /**
     * Submit IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicIB(): void
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        // find opening balance:
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(100, (float)$account->transactions()->first()->amount);
    }

    /**
     * Submit empty (amount = 0) IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicIBZero(): void
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

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
    public function testCreateBasicIban(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => 'NL02ABNA0870809585',
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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
        $this->assertEquals('NL02ABNA0870809585', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);
    }

    /**
     * Add invalid IBAN.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicInvalidIban(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => 'NL1XRABO032674X238',
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);
    }

    /**
     * Submit IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicNegativeIB(): void
    {

        $data = [
            'account_type_id'    => null,
            'accountType'        => 'asset',
            'iban'               => null,
            'name'               => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        // find opening balance:
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(-100, (float)$account->transactions()->first()->amount);
    }

    /**
     * Add some notes to asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateBasicNotes(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        $note = $account->notes()->first();
        $this->assertEquals('Hello!', $note->text);
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateCurrencyCode(): void
    {
        $currency = TransactionCurrency::where('code', 'CAD')->first();
        $data     = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'currency_code'   => $currency->code,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        // get the currency ID:
        /** @var AccountMeta $meta */
        $currencyId = $account->accountMeta()->where('name', 'currency_id')->first();
        $this->assertNotNull($currencyId);
        $this->assertEquals((int)$currency->id, (int)$currencyId->data);
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateCurrencyId(): void
    {
        $currency = TransactionCurrency::where('code', 'USD')->first();
        $data     = [
            'account_type_id' => null,
            'accountType'     => 'asset',
            'iban'            => null,
            'currency_id'     => $currency->id,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
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

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNotNull($meta);
        $this->assertEquals('defaultAsset', $meta->data);

        // get the currency ID:
        /** @var AccountMeta $meta */
        $currencyId = $account->accountMeta()->where('name', 'currency_id')->first();
        $this->assertNotNull($currencyId);
        $this->assertEquals($currency->id, $currencyId->data);
    }

    /**
     * Should return existing account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     */
    public function testCreateExisting(): void
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

    /**
     * Can't find account type.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateNoType(): void
    {

        $data = [
            'account_type_id' => null,
            'accountType'     => 'bla-bla',
            'iban'            => null,
            'name'            => 'Basic asset account #' . random_int(1, 10000),
            'virtualBalance'  => null,
            'active'          => true,
            'accountRole'     => 'defaultAsset',
        ];

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertContains('AccountFactory::create() was unable to find account type #0 ("bla-bla").', $e->getMessage());
        }
    }

    /**
     * Test only for existing account because the rest has been covered by other tests.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Factory\AccountMetaFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testFindOrCreate(): void
    {
        /** @var Account $account */
        $account = $this->getRandomRevenue();
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        Log::debug(sprintf('Searching for account #%d with name "%s"', $account->id, $account->name));

        $result = $factory->findOrCreate($account->name, $account->accountType->type);
        $this->assertEquals($result->id, $account->id);
    }

}
