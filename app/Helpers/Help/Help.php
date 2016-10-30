<?php
/**
 * Help.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Help;

use Cache;
use League\CommonMark\CommonMarkConverter;
use Requests;
use Route;

/**
 * Class Help
 *
 * @package FireflyIII\Helpers\Help
 */
class Help implements HelpInterface
{

    /**
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromCache(string $route, string $language): string
    {
        return Cache::get('help.' . $route . '.' . $language);
    }

    /**
     * @param string $language
     * @param string $route
     *
     * @return string
     */
    public function getFromGithub(string $language, string $route): string
    {

        $uri     = sprintf('https://raw.githubusercontent.com/firefly-iii/help/master/%s/%s.md', $language, $route);
        $content = '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>';
        $result  = Requests::get($uri);

        if ($result->status_code === 200) {
            $content = $result->body;
        }


        if (strlen(trim($content)) == 0) {
            $content = '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>';
        }
        $converter = new CommonMarkConverter();
        $content   = $converter->convertToHtml($content);

        return $content;

    }

    /**
     *
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route):bool
    {
        return Route::has($route);
    }

    /**
     * @param string $route
     * @param string $language
     *
     * @return bool
     */
    public function inCache(string $route, string $language):bool
    {
        return Cache::has('help.' . $route . '.' . $language);
    }

    /**
     *
     * @param string $route
     * @param string $language
     * @param string $content
     *
     * @internal param $title
     */
    public function putInCache(string $route, string $language, string $content)
    {
        Cache::put('help.' . $route . '.' . $language, $content, 10080); // a week.
    }
}
