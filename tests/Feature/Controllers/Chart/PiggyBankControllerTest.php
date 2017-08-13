<?php
/**
 * PiggyBankControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * @package Tests\Feature\Controllers\Chart
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
