<?php
/**
 * AboutController.php
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

use DB;
use FireflyIII\Transformers\UserTransformer;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;


/**
 * Class AboutController
 */
class AboutController extends Controller
{
    /**
     * AccountController constructor.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function about()
    {
        $search        = ['~', '#'];
        $replace       = ['\~', '# '];
        $phpVersion    = str_replace($search, $replace, PHP_VERSION);
        $phpOs         = str_replace($search, $replace, php_uname());
        $currentDriver = DB::getDriverName();
        $data
                       = [
            'version'     => config('firefly.version'),
            'api_version' => config('firefly.api_version'),
            'php_version' => $phpVersion,
            'os'          => $phpOs,
            'driver'      => $currentDriver,

        ];

        return response()->json(['data' => $data], 200)->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item(auth()->user(), new UserTransformer($this->parameters), 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

}