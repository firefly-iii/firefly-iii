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

use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\System\UpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Binder\EitherConfigKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/configuration/getConfiguration
     *
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

        return response()->api($return);
    }

    /**
     * Get all config values.
     * @throws FireflyException
     */
    private function getDynamicConfiguration(): array
    {
        $isDemoSite  = FireflyConfig::get('is_demo_site');
        $updateCheck = FireflyConfig::get('permission_update_check');
        $lastCheck   = FireflyConfig::get('last_update_check');
        $singleUser  = FireflyConfig::get('single_user_mode');

        return [
            'is_demo_site'            => $isDemoSite?->data,
            'permission_update_check' => null === $updateCheck ? null : (int) $updateCheck->data,
            'last_update_check'       => null === $lastCheck ? null : (int) $lastCheck->data,
            'single_user_mode'        => $singleUser?->data,
        ];
    }

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
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/configuration/getSingleConfiguration
     */
    public function show(string $configKey): JsonResponse
    {
        $data     = [];
        $dynamic  = $this->getDynamicConfiguration();
        $shortKey = str_replace('configuration.', '', $configKey);
        if (str_starts_with($configKey, 'configuration.')) {
            $data = [
                'title'    => $configKey,
                'value'    => $dynamic[$shortKey],
                'editable' => true,
            ];
        }
        if (!str_starts_with($configKey, 'configuration.')) {
            $data = [
                'title'    => $configKey,
                'value'    => config($configKey),
                'editable' => false,
            ];
        }

        return response()->api(['data' => $data])->header('Content-Type', self::JSON_CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/configuration/setConfiguration
     *
     * Update the configuration.
     *
     * @param UpdateRequest $request
     * @param string $name
     * @return JsonResponse
     * @throws FireflyException
     * @throws ValidationException
     */
    public function update(UpdateRequest $request, string $name): JsonResponse
    {
        $rules     = ['value' => 'required'];
        if (!$this->repository->hasRole(auth()->user(), 'owner')) {
            $messages = ['value' => '200005: You need the "owner" role to do this.'];
            Validator::make([], $rules, $messages)->validate();
        }
        $data      = $request->getAll();
        $shortName = str_replace('configuration.', '', $name);

        FireflyConfig::set($shortName, $data['value']);

        // get updated config:
        $newConfig = $this->getDynamicConfiguration();
        $data      = [
            'title'    => $name,
            'value'    => $newConfig[$shortName],
            'editable' => true,
        ];

        return response()->api(['data' => $data])->header('Content-Type', self::CONTENT_TYPE);
    }
}
