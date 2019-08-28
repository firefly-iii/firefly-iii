<?php
/**
 * CurrencyExchangeRateTransformerTest.php
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


use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Transformers\CurrencyExchangeRateTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 *
 * Class CurrencyExchangeRateTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CurrencyExchangeRateTransformerTest extends TestCase
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
     * @covers \FireflyIII\Transformers\CurrencyExchangeRateTransformer
     */
    public function testBasic()
    {
        $date                  = new Carbon;
        $eur                   = TransactionCurrency::whereCode('EUR')->first();
        $usd                   = TransactionCurrency::whereCode('USD')->first();
        $cer                   = new CurrencyExchangeRate;
        $cer->from_currency_id = $eur->id;
        $cer->to_currency_id   = $usd->id;
        $cer->created_at       = new Carbon;
        $cer->updated_at       = new Carbon;
        $cer->rate             = 1.5;
        $cer->date             = $date;

        $parameters = new ParameterBag;
        $parameters->set('amount', '100');

        $transformer = app(CurrencyExchangeRateTransformer::class);
        $transformer->setParameters($parameters);
        $result = $transformer->transform($cer);

        $this->assertEquals($cer->from_currency_id, $result['from_currency_id']);
        $this->assertEquals(150, $result['amount']);


    }
}
