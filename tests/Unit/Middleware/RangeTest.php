<?php
/**
 * RangeTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Middleware;

use FireflyIII\Http\Middleware\Range;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Log;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Preferences;
use Mockery;
use Amount;

/**
 * Class RangeTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RangeTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware(Range::class)->any(
            '/_test/range', function () {
            return view('test.test');
        }
        );
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Range
     */
    public function testMiddlewareAuthenticated(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->andReturn(TransactionJournal::first());
        $this->withoutExceptionHandling();

        $viewPreference = new Preference;
        $viewPreference->data = '1M';

        $langPreference = new Preference;
        $langPreference->data = 'en_US';

        $currPreference = new Preference;
        $currPreference->data = 'EUR';

        $listPreference = new Preference;
        $listPreference->data = 10;


        Preferences::shouldReceive('get')->withArgs(['viewRange','1M'])->atLeast()->once()->andReturn($viewPreference);
        Preferences::shouldReceive('get')->withArgs(['language','en_US'])->atLeast()->once()->andReturn($langPreference);
        Preferences::shouldReceive('get')->withArgs(['list-length',10])->atLeast()->once()->andReturn($listPreference);
        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($this->getEuro());
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');


        $this->be($this->user());
        $response = $this->get('/_test/range');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // view has list length
        $response->assertSeeText('list-length: 10');

        // assert some session stuff?
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Range
     */
    public function testMiddlewareNotAuthenticated(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/range');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
