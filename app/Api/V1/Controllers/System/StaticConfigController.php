<?php
/*
 * EnvController.php
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

namespace FireflyIII\Api\V1\Controllers\System;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Support\Binder\StaticConfigKey;
use Illuminate\Http\JsonResponse;

/**
 * Class StaticConfigController
 *
 * Show specific Firefly III configuration and/or ENV vars.
 */
class StaticConfigController extends Controller
{
    private array $list;

    /**
     * EnvController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->list = StaticConfigKey::$accepted;
    }

    /**
     * Show all available env variables.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $vars = [];
        // show all Firefly III config vars.
        foreach ($this->list as $key) {
            $vars[$key] = config($key);
        }

        return response()->json($vars);
    }

    /**
     * @param string $staticKey
     *
     * @return JsonResponse
     */
    public function show(string $staticKey): JsonResponse
    {
        $response = [$staticKey => config($staticKey)];

        return response()->json($response);
    }
}
