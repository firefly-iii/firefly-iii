<?php
/**
 * PiggyBankEventTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Amount;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\PiggyBankEventTransformer;
use Log;
use Mockery;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class PiggyBankEventTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PiggyBankEventTransformerTest extends TestCase
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
     * Basic test with no meta data.
     *
     * @covers \FireflyIII\Transformers\PiggyBankEventTransformer
     */
    public function testBasic(): void
    {
        // repositories
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock calls:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->atLeast()->once()->andReturn(1);
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn($this->getEuro());

        $event       = $this->getRandomPiggyBankEvent();



        $transformer = app(PiggyBankEventTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $result = $transformer->transform($event);
        $this->assertEquals($event->id, $result['id']);
        $this->assertEquals($event->amount, $result['amount']);
        $this->assertEquals($event->transaction_journal_id, $result['transaction_journal_id']);

    }

    /**
     * Basic test with no currency info.
     *
     * @covers \FireflyIII\Transformers\PiggyBankEventTransformer
     */
    public function testNoCurrency(): void
    {
        // repositories
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $piggyRepos    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $piggyRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock calls:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->atLeast()->once()->andReturn(1);
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->atLeast()->once()->andReturn(null);

        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($this->getEuro())->atLeast()->once();

        $event       = $this->getRandomPiggyBankEvent();
        $transformer = app(PiggyBankEventTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $result = $transformer->transform($event);
        $this->assertEquals($event->id, $result['id']);
        $this->assertEquals($event->amount, $result['amount']);
        $this->assertEquals($event->transaction_journal_id, $result['transaction_journal_id']);

    }
}
