<?php
/**
 * AssetAccountMapperTest.php
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


use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\AssetAccountMapper;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class AssetAccountMapperTest
 */
class AssetAccountMapperTest extends TestCase
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
     * Should return with the given $default account and not the $bad one.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testBadAsset(): void
    {
        $bad     = $this->user()->accounts()->where('account_type_id', 4)->inRandomOrder()->first();
        $default = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();
        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->once()->withArgs([$bad->id])->andReturn($bad);
        $repository->shouldReceive('findNull')->once()->withArgs([$default->id])->andReturn($default);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $mapper->setDefaultAccount($default->id);
        $result = $mapper->map($bad->id, []);
        $this->assertEquals($default->id, $result->id);
    }

    /**
     * Should return with the given $expected account.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testCorrectAsset(): void
    {
        $expected = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();

        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->once()->withArgs([$expected->id])->andReturn($expected);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map($expected->id, []);
        $this->assertEquals($expected->id, $result->id);
    }

    /**
     * Should return with the $default account.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testEmpty(): void
    {
        $default = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();

        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->once()->withArgs([$default->id])->andReturn($default);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $mapper->setDefaultAccount($default->id);
        $result = $mapper->map(null, []);
        $this->assertEquals($default->id, $result->id);

    }

    /**
     * Should return with the $default account.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testEmptyNoDefault(): void
    {
        $fallback = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::ASSET]])->andReturn(
            new Collection([$fallback])
        );

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map(null, []);
        $this->assertEquals($fallback->id, $result->id);

    }

    /**
     * Should search for the given IBAN and return $expected.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testFindByIban(): void
    {
        $searchValue = 'IamIban';
        $expected    = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();
        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByIbanNull')->once()
                   ->withArgs([$searchValue, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])->andReturn($expected);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map(0, ['iban' => $searchValue]);
        $this->assertEquals($expected->id, $result->id);
    }

    /**
     * Should search for the given name and return $expected.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testFindByName(): void
    {
        $searchValue = 'AccountName';
        $expected    = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();
        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByName')->once()
                   ->withArgs([$searchValue, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])->andReturn($expected);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map(0, ['name' => $searchValue]);
        $this->assertEquals($expected->id, $result->id);
    }

    /**
     * Should search for the given number and return $expected.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\AssetAccountMapper
     */
    public function testFindByNumber(): void
    {
        $searchValue = '123456';
        $expected    = $this->user()->accounts()->where('account_type_id', 3)->inRandomOrder()->first();
        // mock repository:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByAccountNumber')->once()
                   ->withArgs([$searchValue, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])->andReturn($expected);

        $mapper = new AssetAccountMapper;
        $mapper->setUser($this->user());
        $result = $mapper->map(0, ['number' => $searchValue]);
        $this->assertEquals($expected->id, $result->id);
    }

}
