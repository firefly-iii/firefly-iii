<?php
/**
 * CurrencyTransformerTest.php
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

namespace Tests\Unit\Transformers;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Transformers\CurrencyTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;


/**
 * Class CurrencyTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CurrencyTransformerTest extends TestCase
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
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\CurrencyTransformer
     */
    public function testBasic(): void
    {
        // mocks and prep:
        $parameters  = new ParameterBag;
        $currency    = $this->getEuro();
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($parameters);

        // action
        $result = $transformer->transform($currency);


        $this->assertEquals($currency->code, $result['code']);
        $this->assertFalse($result['default']);

    }

    /**
     * Basic coverage with default currency
     *
     * @covers \FireflyIII\Transformers\CurrencyTransformer
     */
    public function testDefaultCurrency(): void
    {
        // mocks and prep:
        $parameters = new ParameterBag;
        $currency   = $this->getEuro();
        $parameters->set('defaultCurrency', $currency);
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($parameters);


        // action
        $result = $transformer->transform($currency);


        $this->assertEquals($currency->code, $result['code']);
        $this->assertTrue($result['default']);

    }

}
