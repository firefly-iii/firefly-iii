<?php
/**
 * PiggyBankTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Amount;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\PiggyBankTransformer;
use Log;
use Mockery;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class PiggyBankTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PiggyBankTransformerTest extends TestCase
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
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\PiggyBankTransformer
     */
    public function testBasic(): void
    {
        // mock repositories
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return a currency
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->atLeast()->once()->andReturn('1');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn($this->getEuro());

        // get a note
        $piggyRepos->shouldReceive('getNoteText')->atLeast()->once()->andReturn('I am a note.');

        // get some amounts
        $piggyRepos->shouldReceive('getCurrentAmount')->atLeast()->once()->andReturn('123.45');
        $piggyRepos->shouldReceive('getSuggestedMonthlyAmount')->atLeast()->once()->andReturn('12.45');

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $piggy  = PiggyBank::first();
        $result = $transformer->transform($piggy);

        $this->assertEquals(12.45, $result['save_per_month']);
        $this->assertEquals($piggy->name, $result['name']);
        $this->assertEquals(1, $result['currency_id']);
    }

    /**
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\PiggyBankTransformer
     */
    public function testNoCurrency(): void
    {
        // mock repositories
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return a currency
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->atLeast()->once()->andReturn('1');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn(null);
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($this->getEuro());

        // get a note
        $piggyRepos->shouldReceive('getNoteText')->atLeast()->once()->andReturn('I am a note.');

        // get some amounts
        $piggyRepos->shouldReceive('getCurrentAmount')->atLeast()->once()->andReturn('123.45');
        $piggyRepos->shouldReceive('getSuggestedMonthlyAmount')->atLeast()->once()->andReturn('12.45');

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $piggy  = PiggyBank::first();
        $result = $transformer->transform($piggy);

        $this->assertEquals(12.45, $result['save_per_month']);
        $this->assertEquals($piggy->name, $result['name']);
        $this->assertEquals(1, $result['currency_id']);
    }

}
