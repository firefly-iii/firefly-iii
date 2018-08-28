<?php
/**
 * AccountMetaFactoryTest.php
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


use FireflyIII\Factory\AccountMetaFactory;
use Log;
use Tests\TestCase;

/**
 *
 * Class AccountMetaFactoryTest
 */
class AccountMetaFactoryTest extends TestCase
{


    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Factory\AccountMetaFactory
     */
    public function testCreate(): void
    {
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $data    = [
            'account_id' => $account->id,
            'name'       => 'Some name',
            'data'       => 'Some value',
        ];

        $factory = new AccountMetaFactory;
        $result  = $factory->create($data);
        $this->assertEquals($data['name'], $result->name);
    }

    /**
     * @covers \FireflyIII\Factory\AccountMetaFactory
     */
    public function testCrudDelete(): void
    {
        $factory = new AccountMetaFactory;
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $data    = [
            'account_id' => $account->id,
            'name'       => 'Some name ' . random_int(1, 100000),
            'data'       => 'Some value',
        ];

        $factory->create($data);

        // update existing one
        $result = $factory->crud($account, $data['name'], '');
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\AccountMetaFactory
     */
    public function testCrudExisting(): void
    {
        $factory = new AccountMetaFactory;
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $data    = [
            'account_id' => $account->id,
            'name'       => 'Some name ' . random_int(1, 100000),
            'data'       => 'Some value',
        ];

        $existing = $factory->create($data);

        // update existing one
        $result = $factory->crud($account, $data['name'], 'Some NEW value');
        $this->assertNotNull($result);
        $this->assertEquals($result->account_id, $account->id);
        $this->assertEquals($existing->name, $result->name);
        $this->assertEquals('Some NEW value', $result->data);

    }

    /**
     * @covers \FireflyIII\Factory\AccountMetaFactory
     */
    public function testCrudNew(): void
    {
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $factory = new AccountMetaFactory;
        $result  = $factory->crud($account, 'random name ' . random_int(1, 100000), 'Some value');
        $this->assertNotNull($result);
        $this->assertEquals($result->account_id, $account->id);

    }
}