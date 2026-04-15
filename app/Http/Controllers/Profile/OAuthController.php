<?php
/*
 * OAuthController.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Profile;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    protected bool $internalAuth;
    public function __construct()
    {
        parent::__construct();

        $this->middleware(static function ($request, $next) {
            app('view')->share('title', (string) trans('firefly.oauth_tokens'));
            app('view')->share('mainTitleIcon', 'fa-user');

            return $next($request);
        });
        $authGuard          = config('firefly.authentication_guard');
        $this->internalAuth = 'web' === $authGuard;
        Log::debug(sprintf('ProfileController::__construct(). Authentication guard is "%s"', $authGuard));

        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    public function index() {
        return view('profile.oauth.index');
    }
}
