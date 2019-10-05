<?php
/**
 * AccountFactoryTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
     */
    public function testCreate(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $euro = $this->getEuro();
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($euro);

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
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Test creation of CC asset.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateCC(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);


        $data = [
            'account_type_id'         => null,
            'account_type'            => 'asset',
            'iban'                    => null,
            'name'                    => sprintf('Basic CC account #%d', $this->randomInt()),
            'virtual_balance'         => null,
            'active'                  => true,
            'account_role'            => 'ccAsset',
            'cc_monthly_payment_date' => '2018-01-01',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'ccAsset'])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'cc_monthly_payment_date', '2018-01-01'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'cc_type', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();
        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);
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
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateCurrencyCode(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $metaFactory  = $this->mock(AccountMetaFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $currency = $this->getDollar();
        $data     = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'currency_code'   => $currency->code,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', $currency->id])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, 'USD'])->atLeast()->once()->andReturn($currency);

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);


    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateCurrencyId(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $currency = $this->getDollar();
        $data     = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'currency_id'     => $currency->id,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', $currency->id])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([7, ''])->atLeast()->once()->andReturn($currency);

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

    }

    /**
     * Leave virtual balance empty.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateEmptyVb(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $this->mock(TransactionGroupFactory::class);
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => '',
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

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
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Should return existing account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     */
    public function testCreateExisting(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $this->mock(AccountMetaFactory::class);
        $existing = $this->getRandomAsset();
        $data     = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'name'            => $existing->name,
            'virtual_balance' => null,
            'iban'            => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];


        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->id, $existing->id);
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateExpense(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        $metaFactory = $this->mock(AccountMetaFactory::class);
        $data        = [
            'account_type_id' => null,
            'account_type'    => 'expense',
            'iban'            => null,
            'name'            => sprintf('Basic expense account #%d', $this->randomInt()),
            'virtual_balance' => '1243',
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest_period', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

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
        $this->assertEquals('0', $account->virtual_balance);

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNull($meta);
        $account->forceDelete();
    }

    /**
     * Create an expense account. This overrules the virtual balance.
     * Role should not be set. This time set type name, not ID.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateExpenseFullType(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $data            = [
            'account_type_id' => null,
            'account_type'    => 'Expense account',
            'iban'            => null,
            'name'            => sprintf('Basic expense account #%d', $this->randomInt()),
            'virtual_balance' => '1243',
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];


        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest', ''])->atLeast()->once()->andReturnNull();
        ////$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest_period', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

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
        $this->assertEquals('0', $account->virtual_balance);

        // get the role:
        /** @var AccountMeta $meta */
        $meta = $account->accountMeta()->where('name', 'accountRole')->first();
        $this->assertNull($meta);
        $account->forceDelete();
    }

    /**
     * Add valid IBAN.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateIban(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => 'NL02ABNA0870809585',
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];


        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('NL02ABNA0870809585', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Add invalid IBAN.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateInvalidIban(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => 'NL1XRABO032674X238',
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

    }

    /**
     * Submit IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateNegativeIB(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $groupFactory    = $this->mock(TransactionGroupFactory::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
            'account_type_id'      => null,
            'account_type'         => 'asset',
            'iban'                 => null,
            'name'                 => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance'      => null,
            'active'               => true,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '-100',
            'opening_balance_date' => new Carbon('2018-01-01'),
            'currency_id'          => 1,
        ];

        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($langPreference);

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $groupFactory->shouldReceive('setUser')->atLeast()->once();
        $groupFactory->shouldReceive('create')->atLeast()->once();

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([1, ''])->atLeast()->once()->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Can't find account type.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateNoType(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $this->mock(AccountMetaFactory::class);
        $data = [
            'account_type_id' => null,
            'account_type'    => 'bla-bla',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertStringContainsString('AccountFactory::create() was unable to find account type #0 ("bla-bla").', $e->getMessage());
        }
    }

    /**
     * Add some notes to asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateNotes(): void
    {
        Log::info(sprintf('Now in test %s', __METHOD__));
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $data = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
            'notes'           => 'Hello!',
        ];

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

        $note = $account->notes()->first();
        $this->assertEquals('Hello!', $note->text);
    }

    /**
     * Submit valid opening balance data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateOB(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $groupFactory    = $this->mock(TransactionGroupFactory::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
            'account_type_id'      => null,
            'account_type'         => 'asset',
            'iban'                 => null,
            'name'                 => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance'      => null,
            'active'               => true,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '100',
            'opening_balance_date' => new Carbon('2018-01-01'),
            'currency_id'          => 1,
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $groupFactory->shouldReceive('setUser')->atLeast()->once();
        $groupFactory->shouldReceive('create')->atLeast()->once();

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([1, ''])->atLeast()->once()->andReturn($euro);

        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($langPreference);

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }

        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);
    }

    /**
     * Submit empty (amount = 0) IB data for asset account.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateOBZero(): void
    {
        // mock repositories:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
            'account_type_id'      => null,
            'account_type'         => 'asset',
            'iban'                 => null,
            'name'                 => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance'      => null,
            'active'               => true,
            'account_role'         => 'defaultAsset',
            'opening_balance'      => '0.0',
            'opening_balance_date' => new Carbon('2018-01-01'),
            'currency_id'          => 1,
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([1, ''])->atLeast()->once()->andReturn($euro);

        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($langPreference);

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());

        try {
            $account = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());

            return;
        }


        // assert stuff about account:
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals(AccountType::ASSET, $account->accountType->type);
        $this->assertEquals('', $account->iban);
        $this->assertTrue($account->active);
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * Test minimal set of data to make factory work (asset account).
     *
     * Add some details
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testCreateWithNumbers(): void
    {
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $this->mock(TransactionGroupFactory::class);
        $euro = $this->getEuro();
        $data = [
            'account_type_id'   => null,
            'account_type'      => 'asset',
            'iban'              => null,
            'name'              => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance'   => null,
            'active'            => true,
            'account_role'      => 'defaultAsset',
            'BIC'               => 'BIC',
            'include_net_worth' => false,
            'account_number'    => '1234',
        ];

        // mock calls to the repository:
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', '1234'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', 'BIC'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', false])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($euro);

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
        $this->assertEquals('0', $account->virtual_balance);

        $account->forceDelete();
    }

    /**
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testFindOrCreate(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $this->mock(AccountMetaFactory::class);
        /** @var Account $account */
        $account = $this->getRandomRevenue();


        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        Log::debug(sprintf('Searching for account #%d with name "%s"', $account->id, $account->name));

        $result = $factory->findOrCreate($account->name, $account->accountType->type);
        $this->assertEquals($result->id, $account->id);
    }

    /**
     * Test only for existing account because the rest has been covered by other tests.
     *
     * @covers \FireflyIII\Factory\AccountFactory
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testFindOrCreateNew(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(TransactionGroupFactory::class);
        $metaFactory = $this->mock(AccountMetaFactory::class);
        /** @var Account $account */
        $account         = $this->getRandomRevenue();
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);

        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_number', ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'BIC', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'include_net_worth', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest', ''])->atLeast()->once()->andReturnNull();
        //$metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'interest_period', ''])->atLeast()->once()->andReturnNull();
        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();

        $euro = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro);

        $name = sprintf('New %s', $account->name);

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user());
        Log::debug(sprintf('Searching for account #%d with name "%s"', $account->id, $account->name));

        $result = $factory->findOrCreate($name, $account->accountType->type);
        $this->assertNotEquals($result->id, $account->id);

        $result->forceDelete();

    }

}
