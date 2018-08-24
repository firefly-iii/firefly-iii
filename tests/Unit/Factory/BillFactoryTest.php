<?php
/**
 * BillFactoryTest.php
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


use FireflyIII\Factory\BillFactory;
use Log;
use Tests\TestCase;

/**
 * Class BillFactoryTest
 */
class BillFactoryTest extends TestCase
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
     * Create basic bill with minimum data.
     *
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateBasic(): void
    {
        $data = [
            'name'          => 'Some new bill #' . random_int(1, 10000),
            'amount_min'    => '5',
            'currency_id'   => 1,
            'currency_code' => '',
            'amount_max'    => '10',
            'date'          => '2018-01-01',
            'repeat_freq'   => 'monthly',
            'skip'          => 0,
            'automatch'     => true,
            'active'        => true,
            'notes'         => 'Hello!',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals($data['repeat_freq'], $bill->repeat_freq);
        $note = $bill->notes()->first();
        $this->assertEquals($data['notes'], $note->text);

    }

    /**
     * Create basic bill with minimum data.
     *
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateEmptyNotes(): void
    {
        $data = [
            'name'          => 'Some new bill #' . random_int(1, 10000),
            'amount_min'    => '5',
            'amount_max'    => '10',
            'date'          => '2018-01-01',
            'repeat_freq'   => 'monthly',
            'currency_id'   => 1,
            'currency_code' => '',
            'skip'          => 0,
            'automatch'     => true,
            'active'        => true,
            'notes'         => '',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals($data['repeat_freq'], $bill->repeat_freq);
        $this->assertEquals(0, $bill->notes()->count());

    }

    /**
     * Find by ID
     *
     * @covers \FireflyIII\Factory\BillFactory
     *
     */
    public function testFindById(): void
    {
        $existing = $this->user()->piggyBanks()->first();
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $piggy = $factory->find($existing->id, null);
        $this->assertEquals($existing->id, $piggy->id);
    }

    /**
     * Find by name
     *
     * @covers \FireflyIII\Factory\BillFactory
     *
     */
    public function testFindByName(): void
    {
        $existing = $this->user()->bills()->first();
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $piggy = $factory->find(null, $existing->name);

        $this->assertEquals($existing->id, $piggy->id);
    }

    /**
     * Find by unknown name
     *
     * @covers \FireflyIII\Factory\BillFactory
     *
     */
    public function testFindByUnknownName(): void
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $piggy = $factory->find(null, 'I dont exist' . random_int(1, 10000));

        $this->assertNull($piggy);
    }

    /**
     * Find NULL
     *
     * @covers \FireflyIII\Factory\BillFactory
     *
     */
    public function testFindNull(): void
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->find(null, null));
    }

}
