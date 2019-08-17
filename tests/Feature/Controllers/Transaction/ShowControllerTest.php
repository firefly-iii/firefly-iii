<?php
declare(strict_types=1);
/**
 * ShowControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Transaction;


use Amount;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ShowControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShowControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\ShowController
     */
    public function testShow(): void
    {
        $this->mockDefaultSession();

        // values
        $withdrawal = $this->getRandomWithdrawalGroup();
        $array      = $this->getRandomWithdrawalGroupAsArray();

        $array['transactions'][0]['foreign_amount']                  = '10';
        $array['transactions'][0]['foreign_currency_symbol']         = 'x';
        $array['transactions'][0]['foreign_currency_decimal_places'] = 2;

        $groupRepository = $this->mock(TransactionGroupRepositoryInterface::class);
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $transformer     = $this->mock(TransactionGroupTransformer::class);

        // mock for transformer:
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transformObject')->atLeast()->once()->andReturn($array);

        // mock for repos
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $groupRepository->shouldReceive('getPiggyEvents')->atLeast()->once()->andReturn([]);
        $groupRepository->shouldReceive('getAttachments')->atLeast()->once()->andReturn([]);
        $groupRepository->shouldReceive('getLinks')->atLeast()->once()->andReturn([]);

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');


        $this->be($this->user());
        $response = $this->get(route('transactions.show', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
