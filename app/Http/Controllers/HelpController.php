<?php
/**
 * HelpController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
