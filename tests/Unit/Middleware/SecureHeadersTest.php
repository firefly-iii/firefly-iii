<?php
/**
 * SecureHeadersTest.php
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

namespace Tests\Unit\Middleware;

use Config;
use FireflyIII\Http\Middleware\SecureHeaders;
use Log;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class SecureHeadersTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SecureHeadersTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware(SecureHeaders::class)->any(
            '/_test/secureheaders', static function () {
            return view('test.test');
        }
        );
    }

    /**
     * @covers \FireflyIII\Http\Middleware\SecureHeaders
     */
    public function testMiddlewareBasic(): void
    {
        $response = $this->get('/_test/secureheaders');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // verify headers

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; object-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline' ; style-src 'self' 'unsafe-inline'; base-uri 'self'; font-src 'self' data:; connect-src 'self'; img-src 'self' data: https://api.tiles.mapbox.com ; manifest-src 'self'; form-action 'self'");
        $response->assertheader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('X-Frame-Options', 'deny');
        $response->assertheader('X-Content-Type-Options', 'nosniff');
        $response->assertheader('Referrer-Policy', 'no-referrer');
        $response->assertheader('Feature-Policy', "geolocation 'none'; midi 'none'; sync-xhr 'self'; microphone 'none'; camera 'none'; magnetometer 'none'; gyroscope 'none'; speaker 'none'; fullscreen 'self'; payment 'none'");
    }

    /**
     * @covers \FireflyIII\Http\Middleware\SecureHeaders
     */
    public function testMiddlewareGoogleAnalytics(): void
    {
        // response changes when config value is different.
        Config::set('firefly.analytics_id', 'abc');

        $response = $this->get('/_test/secureheaders');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // verify headers

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; object-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline' www.googletagmanager.com/gtag/js https://www.google-analytics.com/analytics.js; style-src 'self' 'unsafe-inline'; base-uri 'self'; font-src 'self' data:; connect-src 'self'; img-src 'self' data: https://api.tiles.mapbox.com https://www.google-analytics.com/; manifest-src 'self'; form-action 'self'");
        $response->assertheader('X-XSS-Protection', '1; mode=block');
        $response->assertheader('X-Content-Type-Options', 'nosniff');
        $response->assertheader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Frame-Options', 'deny');
        $response->assertheader('Feature-Policy', "geolocation 'none'; midi 'none'; sync-xhr 'self'; microphone 'none'; camera 'none'; magnetometer 'none'; gyroscope 'none'; speaker 'none'; fullscreen 'self'; payment 'none'");
    }


    /**
     * @covers \FireflyIII\Http\Middleware\SecureHeaders
     */
    public function testMiddlewareFrameHeader(): void
    {
        // response changes when config value is different.
        Config::set('firefly.disable_frame_header', true);

        $response = $this->get('/_test/secureheaders');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // verify headers

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; object-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline' ; style-src 'self' 'unsafe-inline'; base-uri 'self'; font-src 'self' data:; connect-src 'self'; img-src 'self' data: https://api.tiles.mapbox.com ; manifest-src 'self'; form-action 'self'");
        $response->assertheader('X-XSS-Protection', '1; mode=block');
        $response->assertheader('X-Content-Type-Options', 'nosniff');
        $response->assertheader('Referrer-Policy', 'no-referrer');
        $response->assertHeaderMissing('X-Frame-Options');
        $response->assertheader('Feature-Policy', "geolocation 'none'; midi 'none'; sync-xhr 'self'; microphone 'none'; camera 'none'; magnetometer 'none'; gyroscope 'none'; speaker 'none'; fullscreen 'self'; payment 'none'");
    }

}
