<?php
/**
 * TwoFactorControllerTest.php
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

namespace Tests\Feature\Controllers\Auth;

use FireflyIII\Models\Preference;
use Google2FA;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class TwoFactorControllerTest
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

        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.lost'));
        $response->assertStatus(200);
    }
}
