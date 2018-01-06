<?php
/**
 * ImportAccountTest.php
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

namespace Tests\Unit\Import\Object;

use FireflyIII\Import\Object\ImportAccount;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Class ImportAccountTest
 */
class ImportAccountTest extends TestCase
{

    /**
     * Should error because it requires a default asset account.
     *
     * @covers \FireflyIII\Import\Object\ImportAccount::__construct
     * @covers \FireflyIII\Import\Object\ImportAccount::getAccount
     * @covers \FireflyIII\Import\Object\ImportAccount::store
     * @covers \FireflyIII\Import\Object\ImportAccount::findMappedObject
     * @covers \FireflyIII\Import\Object\ImportAccount::findExistingObject
     * @covers \FireflyIII\Import\Object\ImportAccount::getMappedObject
     */
    public function testBasic()
    {
        // mock stuff
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $accountType = AccountType::where('type', AccountType::ASSET)->first();
        $account     = Account::find(1);

        // mock calls:
        $repository->shouldReceive('setUser')->once()->withArgs([Mockery::any()]);
        $repository->shouldReceive('getAccountType')->twice()->withArgs([AccountType::ASSET])->andReturn($accountType);
        $repository->shouldReceive('getAccountsByType')->twice()->withArgs([[AccountType::ASSET]])->andReturn(new Collection());
        $repository->shouldReceive('find')->once()->withArgs([1])->andReturn($account);

        // create import account.
        $importAccount = new ImportAccount;
        $importAccount->setUser($this->user());
        $importAccount->setDefaultAccountId(1);
        $found = $importAccount->getAccount();
        $this->assertEquals(1, $found->id);

    }

    /**
     * Should error because it requires a default asset account.
     *
     * @covers \FireflyIII\Import\Object\ImportAccount::__construct
     * @covers \FireflyIII\Import\Object\ImportAccount::getAccount
     * @covers \FireflyIII\Import\Object\ImportAccount::store
     * @covers \FireflyIII\Import\Object\ImportAccount::findMappedObject
     * @covers \FireflyIII\Import\Object\ImportAccount::findExistingObject
     * @covers \FireflyIII\Import\Object\ImportAccount::getMappedObject
     */
    public function testEmptyMappingAccountId()
    {
        // mock stuff
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $accountType = AccountType::where('type', AccountType::ASSET)->first();
        $account     = Account::find(1);

        // mock calls:
        $repository->shouldReceive('setUser')->once()->withArgs([Mockery::any()]);
        $repository->shouldReceive('getAccountType')->once()->withArgs([AccountType::ASSET])->andReturn($accountType);
        //$repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::ASSET]])->andReturn(new Collection());
        //$repository->shouldReceive('find')->once()->withArgs([1])->andReturn($account);

        // create import account.
        $importAccount = new ImportAccount;
        $importAccount->setUser($this->user());
        $importAccount->setDefaultAccountId(1);

        // add an account id:
        $accountId = [
            'role'   => 'account-id',
            'mapped' => null,
            'value'  => 2,
        ];
        $importAccount->setAccountId($accountId);


        $found = $importAccount->getAccount();
        $this->assertEquals(2, $found->id);

    }

    /**
     * @covers                   \FireflyIII\Import\Object\ImportAccount::__construct
     * @covers                   \FireflyIII\Import\Object\ImportAccount::getAccount
     * @covers                   \FireflyIII\Import\Object\ImportAccount::store
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage ImportAccount cannot continue without a default account to fall back on.
     */
    public function testNoAccount()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once()->withArgs([Mockery::any()]);
        $importAccount = new ImportAccount;
        $importAccount->setUser($this->user());
        $importAccount->getAccount();
    }

    /**
     * @covers                   \FireflyIII\Import\Object\ImportAccount::__construct
     * @covers                   \FireflyIII\Import\Object\ImportAccount::getAccount
     * @covers                   \FireflyIII\Import\Object\ImportAccount::store
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage ImportAccount cannot continue without user.
     */
    public function testNoUser()
    {
        $this->mock(AccountRepositoryInterface::class);
        $importAccount = new ImportAccount;
        $importAccount->getAccount();
    }
}