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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use Log;
use Preferences;
use Response;

/**
 * Class HelpController
 *
 * @package FireflyIII\Http\Controllers
 */
class HelpController extends Controller
{

    /** @var HelpInterface */
    private $help;

    /**
     * HelpController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->help = app(HelpInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param               $route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $route)
    {
        $language = Preferences::get('language', config('firefly.default_language', 'en_US'))->data;
        $html     = $this->getHelpText($route, $language);

        return Response::json(['html' => $html]);
    }

    /**
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    private function getHelpText(string $route, string $language): string
    {
        // get language and default variables.

        $content = '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>';

        // if no such route, log error and return default text.
        if (!$this->help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return $content;
        }

        // help content may be cached:
        if ($this->help->inCache($route, $language)) {
            $content = $this->help->getFromCache($route, $language);
            Log::debug(sprintf('Help text %s was in cache.', $language));

            return $content;
        }

        // get help content from Github:
        $content = $this->help->getFromGithub($route, $language);

        // content will have 0 length when Github failed. Try en_US when it does:
        if (strlen($content) === 0) {
            $language = 'en_US';

            // also check cache first:
            if ($this->help->inCache($route, $language)) {
                Log::debug(sprintf('Help text %s was in cache.', $language));
                $content = $this->help->getFromCache($route, $language);

                return $content;
            }

            $content = $this->help->getFromGithub($route, $language);
        }

        // help still empty?
        if (strlen($content) !== 0) {
            $this->help->putInCache($route, $language, $content);

            return $content;
        }

        return '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>';
    }
}
