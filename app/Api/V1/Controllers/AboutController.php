<?php

/**
 * AboutController.php
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

namespace FireflyIII\Api\V1\Controllers;

use DB;
use FireflyIII\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Returns basic information about this installation.
 *
 * @codeCoverageIgnore
 * Class AboutController.
 */
class AboutController extends Controller
{
    /**
     * Returns system information.
     *
     * @return JsonResponse
     */
    public function about(): JsonResponse
    {
        $search        = ['~', '#'];
        $replace       = ['\~', '# '];
        $phpVersion    = str_replace($search, $replace, PHP_VERSION);
        $phpOs         = str_replace($search, $replace, PHP_OS);
        $currentDriver = DB::getDriverName();


        $data
            = [
            'version'     => config('firefly.version'),
            'api_version' => config('firefly.api_version'),
            'php_version' => $phpVersion,
            'os'          => $phpOs,
            'driver'      => $currentDriver,
        ];

        return response()->json(['data' => $data])->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Returns information about the user.
     *
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $manager = $this->getManager();

        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item(auth()->user(), $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}
