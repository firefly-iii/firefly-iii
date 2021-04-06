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
use FireflyIII\Api\V1\Requests\System\UpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Binder\EitherConfigKey;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends Controller
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
     * @return JsonResponse
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        try {
            $dynamicData = $this->getDynamicConfiguration();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException('200030: Could not load config variables.', 0, $e);
        }
        $staticData = $this->getStaticConfiguration();
        $return     = [];
        foreach ($dynamicData as $key => $value) {
            $return[] = [
                'title'    => sprintf('configuration.%s', $key),
                'value'    => $value,
                'editable' => true,
            ];
        }
        foreach ($staticData as $key => $value) {
            $return[] = [
                'title'    => $key,
                'value'    => $value,
                'editable' => false,
            ];
        }

        return response()->json($return);
    }

    /**
     * Get all config values.
     *
     * @return array
     * @throws FireflyException
     */
    private function getDynamicConfiguration(): array
    {
        $isDemoSite  = app('fireflyconfig')->get('is_demo_site');
        $updateCheck = app('fireflyconfig')->get('permission_update_check');
        $lastCheck   = app('fireflyconfig')->get('last_update_check');
        $singleUser  = app('fireflyconfig')->get('single_user_mode');

        return [
            'is_demo_site'            => null === $isDemoSite ? null : $isDemoSite->data,
            'permission_update_check' => null === $updateCheck ? null : (int)$updateCheck->data,
            'last_update_check'       => null === $lastCheck ? null : (int)$lastCheck->data,
            'single_user_mode'        => null === $singleUser ? null : $singleUser->data,
        ];
    }

    /**
     * @return array
     */
    private function getStaticConfiguration(): array
    {
        $list   = EitherConfigKey::$static;
        $return = [];
        foreach ($list as $key) {
            $return[$key] = config($key);
        }

        return $return;
    }

    /**
     * @param string $configKey
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function show(string $configKey): JsonResponse
    {
        $data     = [];
        $dynamic  = $this->getDynamicConfiguration();
        $shortKey = str_replace('configuration.', '', $configKey);
        if ('configuration.' === substr($configKey, 0, 14)) {
            $data = [
                'title'    => $configKey,
                'value'    => $dynamic[$shortKey],
                'editable' => true,
            ];
        }
        if ('configuration.' !== substr($configKey, 0, 14)) {
            $data = [
                'title'    => $configKey,
                'value'    => config($configKey),
                'editable' => false,
            ];
        }

        return response()->json(['data' => $data])->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Update the configuration.
     *
     * @param UpdateRequest $request
     * @param string        $name
     *
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $name): JsonResponse
    {
        if (!$this->repository->hasRole(auth()->user(), 'owner')) {
            throw new FireflyException('200005: You need the "owner" role to do this.'); // @codeCoverageIgnore
        }
        $data      = $request->getAll();
        $shortName = str_replace('configuration.', '', $name);

        app('fireflyconfig')->set($shortName, $data['value']);

        // get updated config:
        $newConfig = $this->getDynamicConfiguration();
        $data      = [
            'title'    => $name,
            'value'    => $newConfig[$shortName],
            'editable' => true,
        ];

        return response()->json(['data' => $data])->header('Content-Type', self::CONTENT_TYPE);
    }
}
