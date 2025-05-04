<?php

/**
 * SecureHeaders.php
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

namespace FireflyIII\Http\Middleware;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/**
 * Class SecureHeaders
 */
class SecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle(Request $request, \Closure $next)
    {
        // generate and share nonce.
        $nonce              = base64_encode(random_bytes(16));
        Vite::useCspNonce($nonce);
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            Debugbar::getJavascriptRenderer()->setCspNonce($nonce);
        }
        app('view')->share('JS_NONCE', $nonce);

        $response           = $next($request);
        $trackingScriptSrc  = $this->getTrackingScriptSource();
        $csp                = [
            "default-src 'none'",
            "object-src 'none'",
            sprintf("script-src 'unsafe-eval' 'strict-dynamic' 'nonce-%1s'", $nonce),
            "style-src 'unsafe-inline' 'self'",
            "base-uri 'self'",
            "font-src 'self' data:",
            sprintf("connect-src 'self' %s", $trackingScriptSrc),
            sprintf("img-src 'self' data: 'nonce-%1s' ", $nonce),
            "manifest-src 'self'",
        ];

        // overrule in development mode
        if (true === env('IS_LOCAL_DEV')) { // @phpstan-ignore-line
            $csp = [
                "default-src 'none'",
                "object-src 'none'",
                sprintf("script-src 'unsafe-eval' 'strict-dynamic' 'nonce-%1s' https://firefly.sd.internal/_debugbar/assets", $nonce),
                "style-src 'unsafe-inline' 'self' https://10.0.0.15:5173/",
                "base-uri 'self'",
                "font-src 'self' data: https://10.0.0.15:5173/",
                sprintf("connect-src 'self' %s https://10.0.0.15:5173/ wss://10.0.0.15:5173/", $trackingScriptSrc),
                sprintf("img-src 'self' data: 'nonce-%1s'", $nonce),
                "manifest-src 'self'",
            ];
        }

        $route              = $request->route();
        $customUrl          = '';
        $authGuard          = (string) config('firefly.authentication_guard');
        $logoutUrl          = (string) config('firefly.custom_logout_url');
        if ('remote_user_guard' === $authGuard && '' !== $logoutUrl) {
            $customUrl = $logoutUrl;
        }

        if (null !== $route && 'oauth/authorize' !== $route->uri) {
            $csp[] = sprintf("form-action 'self' %s", $customUrl);
        }

        $featurePolicies    = [
            "geolocation 'none'",
            "midi 'none'",
            // "notifications 'none'",
            // "push 'self'",
            "sync-xhr 'self'",
            "microphone 'none'",
            "camera 'none'",
            "magnetometer 'none'",
            "gyroscope 'none'",
            // "speaker 'none'",
            // "vibrate 'none'",
            "fullscreen 'self'",
            "payment 'none'",
        ];

        $disableFrameHeader = config('firefly.disable_frame_header');
        $disableCSP         = config('firefly.disable_csp_header');
        if (false === $disableFrameHeader) {
            $response->header('X-Frame-Options', 'deny');
        }
        if (false === $disableCSP && !$response->headers->has('Content-Security-Policy')) {
            $response->header('Content-Security-Policy', implode('; ', $csp));
        }
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('Referrer-Policy', 'no-referrer');
        $response->header('X-Permitted-Cross-Domain-Policies', 'none');
        $response->header('X-Robots-Tag', 'none');
        $response->header('Feature-Policy', implode('; ', $featurePolicies));

        return $response;
    }

    /**
     * Return part of a CSP header allowing scripts from Matomo.
     */
    private function getTrackingScriptSource(): string
    {
        if ('' !== (string) config('firefly.tracker_site_id') && '' !== (string) config('firefly.tracker_url')) {
            return (string) config('firefly.tracker_url');
        }

        return '';
    }
}
