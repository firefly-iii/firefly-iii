<?php

/*
 * AboutController.php
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
use FireflyIII\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Returns basic information about this installation.
 *
 * Class AboutController.
 */
class AboutController extends Controller
{
    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/about/getAbout
     *
     * Returns system information.
     */
    public function about(): JsonResponse
    {
        $search        = ['~', '#'];
        $replace       = ['\~', '# '];
        $phpVersion    = str_replace($search, $replace, PHP_VERSION);
        $phpOs         = str_replace($search, $replace, PHP_OS);
        $currentDriver = \DB::getDriverName();
        $data
                       = [
                           'version'     => config('firefly.version'),
                           'api_version' => config('firefly.version'),
                           'php_version' => $phpVersion,
                           'os'          => $phpOs,
                           'driver'      => $currentDriver,
                       ];

        return response()->api(['data' => $data])->header('Content-Type', self::JSON_CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/about/getCurrentUser
     *
     * Returns information about the user.
     */
    public function user(): JsonResponse
    {
        $manager     = $this->getManager();

        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item(auth()->user(), $transformer, 'users');

        return response()->api($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
