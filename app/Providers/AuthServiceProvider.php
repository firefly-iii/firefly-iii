<?php
/**
 * AuthServiceProvider.php
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

namespace FireflyIII\Providers;

use FireflyIII\Support\Authentication\RemoteUserGuard;
use FireflyIII\Support\Authentication\RemoteUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Log;

/**
 * @codeCoverageIgnore
 * Class AuthServiceProvider
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies
        = [
            // 'FireflyIII\Model' => 'FireflyIII\Policies\ModelPolicy',
        ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        Auth::provider(
            'remote_user_provider', function ($app, array $config) {
            return new RemoteUserProvider($app, $config);
        }
        );

        Auth::extend(
            'remote_user_guard', static function ($app, string $name, array $config) {
            return new RemoteUserGuard(Auth::createUserProvider($config['provider']), $app);
        }
        );

        $this->registerPolicies();


        Passport::routes();
        Passport::tokensExpireIn(now()->addDays(14));


    }
}
