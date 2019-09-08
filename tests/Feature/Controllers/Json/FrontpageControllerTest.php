<?php
/**
 * FrontpageControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;
use Amount;

/**
 * Class FrontpageControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FrontpageControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\FrontpageController
     */
    public function testPiggyBanks(): void
    {
        $this->mockDefaultSession();

        $piggy      = $this->user()->piggyBanks()->first();
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $repository->shouldReceive('getPiggyBanks')->andReturn(new Collection([$piggy]));
        $repository->shouldReceive('getCurrentAmount')->andReturn('10');

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $this->be($this->user());
        $response = $this->get(route('json.fp.piggy-banks'));
        $response->assertStatus(200);
    }
}
