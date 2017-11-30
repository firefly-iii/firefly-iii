<?php
/**
 * PiggyBankControllerTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class PiggyBankControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PiggyBankControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Chart\PiggyBankController::history
     * @covers \FireflyIII\Http\Controllers\Chart\PiggyBankController::__construct
     */
    public function testHistory()
    {
        $generator  = $this->mock(GeneratorInterface::class);
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $event      = factory(PiggyBankEvent::class)->make();

        $repository->shouldReceive('getEvents')->andReturn(new Collection([$event]));
        $generator->shouldReceive('singleSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.piggy-bank.history', [1]));
        $response->assertStatus(200);
    }
}
