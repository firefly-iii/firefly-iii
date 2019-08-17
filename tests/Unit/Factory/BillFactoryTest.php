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


use Amount;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use Log;
use Tests\TestCase;

/**
 * Class BillFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BillFactoryTest extends TestCase
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
     * Create basic bill with minimum data.
     *
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateBasic(): void
    {
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
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

        $currencyFactory->shouldReceive('find')->atLeast()->once()
                        ->withArgs([1, ''])->andReturn($euro);

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals(1, $bill->transaction_currency_id);
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
    public function testCreateDifferentCurrency(): void
    {
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $dollar          = $this->getDollar();
        $data            = [
            'name'          => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'    => '5',
            'currency_code' => $dollar->code,
            'amount_max'    => '10',
            'date'          => '2018-01-01',
            'repeat_freq'   => 'monthly',
            'skip'          => 0,
            'automatch'     => true,
            'active'        => true,
            'notes'         => 'Hello!',
        ];

        $currencyFactory->shouldReceive('find')->atLeast()->once()
                        ->withArgs([0, $dollar->code])->andReturn($dollar);

        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals($dollar->id, $bill->transaction_currency_id);
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
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $euro            = $this->getEuro();
        $data            = [
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

        $currencyFactory->shouldReceive('find')->atLeast()->once()
                        ->withArgs([1, ''])->andReturn($euro);


        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($euro->id, $bill->transaction_currency_id);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals($data['repeat_freq'], $bill->repeat_freq);
        $this->assertEquals(0, $bill->notes()->count());

    }

    /**
     * Create basic bill with minimum data.
     *
     * @covers \FireflyIII\Factory\BillFactory
     * @covers \FireflyIII\Services\Internal\Support\BillServiceTrait
     */
    public function testCreateNoCurrency(): void
    {
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $dollar          = $this->getDollar();
        $data            = [
            'name'        => sprintf('Some new bill #%d', $this->randomInt()),
            'amount_min'  => '5',
            'amount_max'  => '10',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => true,
            'active'      => true,
            'notes'       => 'Hello!',
        ];

        $currencyFactory->shouldReceive('find')->atLeast()->once()
                        ->withArgs([0, ''])->andReturnNull();

        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($dollar);


        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $bill = $factory->create($data);

        $this->assertEquals($data['name'], $bill->name);
        $this->assertEquals($data['amount_min'], $bill->amount_min);
        $this->assertEquals($dollar->id, $bill->transaction_currency_id);
        $this->assertEquals($data['repeat_freq'], $bill->repeat_freq);
        $note = $bill->notes()->first();
        $this->assertEquals($data['notes'], $note->text);

    }

    /**
     * Find by ID
     *
     * @covers \FireflyIII\Factory\BillFactory
     *
     */
    public function testFindById(): void
    {
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $existing        = $this->user()->piggyBanks()->first();
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
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        $existing        = $this->user()->bills()->first();
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
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
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
        $currencyFactory = $this->mock(TransactionCurrencyFactory::class);
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->find(null, null));
    }

}
