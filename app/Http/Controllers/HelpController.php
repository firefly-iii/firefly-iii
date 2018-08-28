<?php
/**
 * HelpController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers;

use FireflyIII\Support\Http\Controllers\RequestInformation;
use Illuminate\Http\JsonResponse;

/**
 * Class HelpController.
 */
class HelpController extends Controller
{
    use RequestInformation;

    /**
     * Show help for a route.
     *
     * @param   $route
     *
     * @return JsonResponse
     */
    public function show(string $route): JsonResponse
    {
        /** @var string $language */
        $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;
        $html     = $this->getHelpText($route, $language);

        return response()->json(['html' => $html]);
    }

}
