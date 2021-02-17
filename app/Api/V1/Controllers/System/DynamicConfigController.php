<?php
/*
 * ConfigurationController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\System;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\ConfigurationRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class DynamicConfigController.
 *
 * @codeCoverageIgnore
 */
class DynamicConfigController extends Controller
{
    private UserRepositoryInterface $repository;

    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(UserRepositoryInterface::class);

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

        return response()->json(['data' => $configData])->header('Content-Type', self::CONTENT_TYPE);
    }
    /**
     * Show all configuration.
     *
     * @param string $value
     * @return JsonResponse
     */
    public function show(string $value): JsonResponse
    {
        $configData = $this->getConfigData();

        return response()->json([$value => $configData[$value]])->header('Content-Type', self::CONTENT_TYPE);
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

        return [
            'is_demo_site'            => null === $isDemoSite ? null : $isDemoSite->data,
            'permission_update_check' => null === $updateCheck ? null : (int)$updateCheck->data,
            'last_update_check'       => null === $lastCheck ? null : (int)$lastCheck->data,
            'single_user_mode'        => null === $singleUser ? null : $singleUser->data,
        ];
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
        if (!$this->repository->hasRole(auth()->user(), 'owner')) {
            throw new FireflyException('200005: You need the "owner" role to do this.'); // @codeCoverageIgnore
        }
        $data = $request->getAll();
        app('fireflyconfig')->set($name, $data['value']);
        $configData = $this->getConfigData();

        return response()->json(['data' => $configData])->header('Content-Type', self::CONTENT_TYPE);
    }
}
