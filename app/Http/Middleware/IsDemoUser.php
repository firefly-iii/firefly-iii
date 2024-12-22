<?php

/**
 * IsDemoUser.php
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

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\Request;

/**
 * Class IsDemoUser.
 */
class IsDemoUser
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        /** @var null|User $user */
        $user       = $request->user();
        if (null === $user) {
            return $next($request);
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        if ($repository->hasRole($user, 'demo')) {
            app('log')->info('User is a demo user.');
            $request->session()->flash('info', (string) trans('firefly.not_available_demo_user'));
            $current  = $request->url();
            $previous = $request->session()->previousUrl();
            if ($current !== $previous) {
                return response()->redirectTo($previous);
            }

            return response()->redirectTo(route('index'));
        }

        return $next($request);
    }
}
