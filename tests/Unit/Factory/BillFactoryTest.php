<?php
/**
 * BillFactoryTest.php
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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Models\ObjectGroup;
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
        self::markTestIncomplete('Incomplete for refactor.');

        return;
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateBasic(): void
    {
        $data = [
            'name'          => sprintf('Some new bill #%d', $this->randomInt()),
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
        try {
            $bill = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals(1, $bill->transaction_currency_id);
        $this->assertEquals($data['repeat_freq'], $bill->repeat_freq);
        $note = $bill->notes()->first();
        $this->assertEquals($data['notes'], $note->text);

    }

    /**
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateQueryException(): void
    {
        $data = [
            'name'          => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'    => '5',
            'currency_id'   => 1,
            'currency_code' => '',
            'amount_max'    => '10',
            'date'          => '2018-01-01',
            'repeat_freq'   => 'monthly',
            'skip'          => 0,
            'automatch'     => true,
            'active'        => 'I AM A STRING',
            'notes'         => 'Hello!',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        try {
            $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertEquals('400000: Could not store bill.', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     * @covers \FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups
     */
    public function testCreateObjectGroup(): void
    {
        $data = [
            'name'         => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'   => '5',
            'amount_max'   => '10',
            'date'         => '2018-01-01',
            'repeat_freq'  => 'monthly',
            'skip'         => 0,
            'object_group' => 'Test',
            'automatch'    => true,
            'active'       => 1,
            'notes'        => 'Hello!',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        try {
            $bill = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
            return;
        }
        $this->assertCount(1, $bill->objectGroups()->get());
    }


    /**
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     * @covers \FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups
     */
    public function testCreateObjectGroupById(): void
    {
        $group = ObjectGroup::create(
            [
                'user_id' => $this->user()->id,
                'title'   => sprintf('Random object group #%d', $this->randomInt()),
                'order'   => 1,
            ]
        );
        $data  = [
            'name'            => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'      => '5',
            'currency_id'     => 1,
            'amount_max'      => '10',
            'date'            => '2018-01-01',
            'repeat_freq'     => 'monthly',
            'skip'            => 0,
            'object_group_id' => $group->id,
            'automatch'       => true,
            'active'          => 1,
            'notes'           => 'Hello!',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        try {
            $bill = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
            return;
        }
        $this->assertCount(1, $bill->objectGroups()->get());
    }


    /**
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateEmptyNotes(): void
    {
        $euro = $this->getEuro();
        $data = [
            'name'          => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'    => '5',
            'amount_max'    => '10',
            'date'          => '2018-01-01',
            'repeat_freq'   => 'monthly',
            'currency_id'   => $euro->id,
            'currency_code' => '',
            'skip'          => 0,
            'automatch'     => true,
            'active'        => true,
            'notes'         => '',
        ];

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        try {
            $bill = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($euro->id, $bill->transaction_currency_id);
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
        $piggy = $factory->find(null, sprintf('I dont exist #%d', $this->randomInt()));

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
