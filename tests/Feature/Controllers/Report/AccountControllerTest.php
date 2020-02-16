<?php
/**
 * AccountControllerTest.php
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

namespace Tests\Feature\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Report\AccountController
     */
    public function testGeneral(): void
    {
        $this->mockDefaultSession();
        $return = [
            'accounts'   => [],
            'start'      => '0',
            'end'        => '0',
            'difference' => '0',
        ];

        $tasker       = $this->mock(AccountTaskerInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $tasker->shouldReceive('getAccountReport')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('report-data.account.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
