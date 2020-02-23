<?php
/**
 * TwoFactorControllerTest.php
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

namespace Tests\Feature\Controllers\Auth;

use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;
use Google2FA;
use Log;
use Preferences;
use Tests\TestCase;
use FireflyConfig;
/**
 * Class TwoFactorControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TwoFactorControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testLostTwoFactor(): void
    {
        $this->be($this->user());
        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->andReturn($falseConfig);

        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.lost'));
        $response->assertStatus(200);
    }
}
