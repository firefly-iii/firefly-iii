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
    public function testCreate(): void
    {
        // mock repositories
        $accountRepos    = $this->mock(AccountRepositoryInterface::class);
        $metaFactory     = $this->mock(AccountMetaFactory::class);
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
            'account_type_id' => null,
            'account_type'    => 'asset',
            'iban'            => null,
            'name'            => sprintf('Basic asset account #%d', $this->randomInt()),
            'virtual_balance' => null,
            'active'          => true,
            'account_role'    => 'defaultAsset',
        ];

        // no currency submitted means: find the EURO:
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($euro);

        $currencyFactory->shouldReceive('find')->withArgs([0, ''])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'account_role', 'defaultAsset'])->atLeast()->once()->andReturnNull();
        $metaFactory->shouldReceive('crud')->withArgs([Mockery::any(), 'currency_id', '1'])->atLeast()->once()->andReturnNull();

        // get opening balance group (null for new accounts)
        $accountRepos->shouldReceive('getOpeningBalanceGroup')->atLeast()->once()->andReturn(null);

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
}
