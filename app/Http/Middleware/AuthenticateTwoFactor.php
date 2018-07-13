<?php
/**
 * AuthenticateTwoFactor.php
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
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Log;

/**
 * Class AuthenticateTwoFactor.
 */
class AuthenticateTwoFactor
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }


    /** @noinspection PhpUnusedParameterInspection */
    /**
     * @param         $request
     * @param Closure $next
     * @param array   ...$guards
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function handle($request, Closure $next, ...$guards)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->auth->guest()) {
            return response()->redirectTo(route('login'));
        }


        $is2faEnabled = app('preferences')->get('twoFactorAuthEnabled', false)->data;
        $has2faSecret = null !== app('preferences')->get('twoFactorAuthSecret');
        /** @noinspection PhpUndefinedMethodInspection */
        $is2faAuthed = 'true' === $request->cookie('twoFactorAuthenticated');

        if ($is2faEnabled && $has2faSecret && !$is2faAuthed) {
            Log::debug('Does not seem to be 2 factor authed, redirect.');

            return response()->redirectTo(route('two-factor.index'));
        }

        return $next($request);
    }

}
