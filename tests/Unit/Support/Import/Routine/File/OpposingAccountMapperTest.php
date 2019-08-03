<?php
/**
 * OpposingAccountMapperTest.php
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

namespace Tests\Unit\Support\Import\Routine\File;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use Log;
use Tests\TestCase;

/**
 * Class OpposingAccountMapperTest
 */
class OpposingAccountMapperTest extends TestCase
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
     *
     * Should return account with given ID (which is of correct type).
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testAccountId(): void
    {
        $expected   = $this->user()->accounts()->where('account_type_id', 4)->inRandomOrder()->first();
        $amount     = '-12.34';
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($expected)->once();
        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $mapper->map(1, $amount, []);
    }

    /**
     *
     * Should return account with given ID (which is of wrong account type).
     * Will continue the search or store a revenue account with that name.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testAccountIdBadType(): void
    {
        $expected       = $this->getRandomRevenue();
        $expected->iban = null;
        $expected->save();
        $amount       = '-12.34';
        $expectedArgs = [
            'name'            => $expected->name,
            'iban'            => null,
            'account_number'   => null,
            'account_type_id' => null,
            'account_type'     => AccountType::EXPENSE,
            'active'          => true,
            'BIC'             => null,
        ];
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($expected)->once();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::EXPENSE]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::ASSET]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::DEBT]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::MORTGAGE]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::LOAN]])->andReturnNull();
        $repository->shouldReceive('store')->withArgs([$expectedArgs])->once()
                   ->andReturn(new Account);


        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $mapper->map(1, $amount, []);
    }

    /**
     *
     * Should return account with given ID (which is of wrong account type).
     * Will continue the search or store a revenue account with that name.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testAccountIdBadTypeIban(): void
    {
        $expected       = $this->user()->accounts()->where('account_type_id', 5)->inRandomOrder()->first();
        $expected->iban = 'AD1200012030200359100100';
        $expected->save();

        $amount       = '-12.34';
        $expectedArgs = [
            'name'            => $expected->name,
            'iban'            => $expected->iban,
            'account_number'   => null,
            'account_type_id' => null,
            'account_type'     => AccountType::EXPENSE,
            'active'          => true,
            'BIC'             => null,
        ];
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($expected)->once();
        $repository->shouldReceive('findByIbanNull')->withArgs([$expected->iban, [AccountType::EXPENSE]])->andReturnNull();
        $repository->shouldReceive('findByIbanNull')->withArgs([$expected->iban, [AccountType::ASSET]])->andReturnNull();
        $repository->shouldReceive('findByIbanNull')->withArgs([$expected->iban, [AccountType::DEBT]])->andReturnNull();
        $repository->shouldReceive('findByIbanNull')->withArgs([$expected->iban, [AccountType::MORTGAGE]])->andReturnNull();
        $repository->shouldReceive('findByIbanNull')->withArgs([$expected->iban, [AccountType::LOAN]])->andReturnNull();

        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::EXPENSE]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::ASSET]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::DEBT]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::MORTGAGE]])->andReturnNull();
        $repository->shouldReceive('findByName')->withArgs([$expected->name, [AccountType::LOAN]])->andReturnNull();
        $repository->shouldReceive('store')->withArgs([$expectedArgs])->once()
                   ->andReturn(new Account);


        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $mapper->map(1, $amount, []);
    }

    /**
     * Amount = negative
     * ID = null
     * other data = null
     * Should call store() with "(no name") for expense account
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testBasic(): void
    {
        $amount       = '-12.34';
        $expectedArgs = [
            'name'            => '(no name)',
            'iban'            => null,
            'account_number'   => null,
            'account_type_id' => null,
            'account_type'     => AccountType::EXPENSE,
            'active'          => true,
            'BIC'             => null,
        ];

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->withArgs([$expectedArgs])->once()
                   ->andReturn(new Account);


        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $mapper->map(null, $amount, []);
    }

    /**
     * Amount = positive
     * ID = null
     * other data = null
     * Should call store() with "(no name") for revenue account
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testBasicPos(): void
    {
        $amount       = '12.34';
        $expectedArgs = [
            'name'            => '(no name)',
            'iban'            => null,
            'account_number'   => null,
            'account_type_id' => null,
            'account_type'     => AccountType::REVENUE,
            'active'          => true,
            'BIC'             => null,
        ];

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->withArgs([$expectedArgs])->once()
                   ->andReturn(new Account);


        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $mapper->map(null, $amount, []);
    }

    /**
     * Amount = negative
     * ID = null
     * other data = null
     * Should call store() with "(no name") for expense account
     *
     * @covers \FireflyIII\Support\Import\Routine\File\OpposingAccountMapper
     */
    public function testFindByAccountNumber(): void
    {
        $expected   = $this->user()->accounts()->where('account_type_id', 4)->inRandomOrder()->first();
        $amount     = '-12.34';
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByAccountNumber')->withArgs(['12345', [AccountType::EXPENSE]])
                   ->andReturn($expected)->once();


        $mapper = new OpposingAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map(null, $amount, ['number' => '12345']);
        $this->assertEquals($result->id, $expected->id);
    }

}
