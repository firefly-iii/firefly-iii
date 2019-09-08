<?php
/**
 * ConfigurationController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\ConfigurationRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class ConfigurationController.
 *
 * @codeCoverageIgnore
 */
class ConfigurationController extends Controller
{


    /** @var UserRepositoryInterface The user repository */
    private $repository;

    /**
     * ConfigurationController constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @noinspection UnusedConstructorDependenciesInspection */
                $this->repository = app(UserRepositoryInterface::class);
                /** @var User $admin */
                $admin = auth()->user();

                if (!$this->repository->hasRole($admin, 'owner')) {
                    throw new FireflyException('No access to method.'); // @codeCoverageIgnore
                }

                return $next($request);
            }
        );
    }

    /**
     * Show all configuration.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $configData = $this->getConfigData();

        return response()->json(['data' => $configData])->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the configuration.
     *
     * @param ConfigurationRequest $request
     * @param string               $name
     *
     * @return JsonResponse
     */
    public function update(ConfigurationRequest $request, string $name): JsonResponse
    {
        $data = $request->getAll();
        app('fireflyconfig')->set($name, $data['value']);
        $configData = $this->getConfigData();

        return response()->json(['data' => $configData])->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Get all config values.
     *
     * @return array
     */
    private function getConfigData(): array
    {
        /** @var Configuration $isDemoSite */
        $isDemoSite = app('fireflyconfig')->get('is_demo_site');
        /** @var Configuration $updateCheck */
        $updateCheck = app('fireflyconfig')->get('permission_update_check');
        /** @var Configuration $lastCheck */
        $lastCheck = app('fireflyconfig')->get('last_update_check');
        /** @var Configuration $singleUser */
        $singleUser = app('fireflyconfig')->get('single_user_mode');
        $data       = [
            'is_demo_site'            => null === $isDemoSite ? null : $isDemoSite->data,
            'permission_update_check' => null === $updateCheck ? null : (int)$updateCheck->data,
            'last_update_check'       => null === $lastCheck ? null : (int)$lastCheck->data,
            'single_user_mode'        => null === $singleUser ? null : $singleUser->data,
        ];

        return $data;
    }
}
