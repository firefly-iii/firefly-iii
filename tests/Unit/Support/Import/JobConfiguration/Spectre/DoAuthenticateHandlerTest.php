<?php
/**
 * DoAuthenticateHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\Spectre;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Support\Import\JobConfiguration\Spectre\DoAuthenticateHandler;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class DoAuthenticateHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DoAuthenticateHandlerTest extends TestCase
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
     * No token in config, but grab it from users preferences.
     *
     * @covers \FireflyIII\Support\Import\Information\GetSpectreTokenTrait
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\DoAuthenticateHandler
     */
    public function testGetNextDataNoToken(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sda-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock stuff:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'ready_to_run']);
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'authenticated']);

        // mock request for a new Token:
        $ctRequest = $this->mock(CreateTokenRequest::class);

        // fake token:
        $carbon = new Carbon();
        $token  = new Token(['token' => 'x', 'expires_at' => $carbon->toW3cString(), 'connect_url' => 'https://']);

        // fake Spectre customer:
        $fakeCustomerPreference       = new Preference;
        $fakeCustomerPreference->name = 'spectre_customer';
        $fakeCustomerPreference->data = [
            'id'         => 1,
            'identifier' => 'fake',
            'secret'     => 'Dumbledore dies',
        ];

        // should try to grab customer from preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'spectre_customer', null])
                   ->andReturn($fakeCustomerPreference)->once();

        // should try to grab token from Spectre:
        $ctRequest->shouldReceive('setUser')->once();
        $ctRequest->shouldReceive('setCustomer')->once();
        $ctRequest->shouldReceive('setUri')->once()->withArgs([route('import.job.status.index', [$job->key])]);
        $ctRequest->shouldReceive('call')->once();
        $ctRequest->shouldReceive('getToken')->once()->andReturn($token);

        $handler = new DoAuthenticateHandler;
        $handler->setImportJob($job);
        $result = [];
        try {
            $result = $handler->getNextData();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
        $this->assertEquals(['token-url' => $token->getConnectUrl()], $result);
    }

}
