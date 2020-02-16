<?php
/**
 * InstallationId.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use Closure;
use FireflyIII\Exceptions\FireflyException;
use Log;
use Ramsey\Uuid\Uuid;

/**
 *
 * Class InstallationId
 */
class InstallationId
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
     *
     * @return mixed
     *
     * @throws FireflyException
     *
     */
    public function handle($request, Closure $next)
    {
        $config = app('fireflyconfig')->get('installation_id', null);
        if (null === $config) {
            $uuid5    = Uuid::uuid5(Uuid::NAMESPACE_URL, 'firefly-iii.org');
            $uniqueId = (string)$uuid5;
            Log::info(sprintf('Created Firefly III installation ID %s', $uniqueId));
            app('fireflyconfig')->set('installation_id', $uniqueId);
        }

        return $next($request);
    }
}