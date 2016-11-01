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
use Log;
use Requests;
use Requests_Exception;
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

        $uri = sprintf('https://raw.githubusercontent.com/firefly-iii/help/master/%s/%s.md', $language, $route);
        Log::debug(sprintf('Trying to get %s...', $uri));
        $content = '';
        try {
            $result = Requests::get($uri);
        } catch (Requests_Exception $e) {
            Log::error($e);

            return '';
        }


        Log::debug(sprintf('Status code is %d', $result->status_code));

        if ($result->status_code === 200) {
            $content = trim($result->body);
        }
        if (strlen($content) > 0) {
            Log::debug('Content is longer than zero. Expect something.');
            $converter = new CommonMarkConverter();
            $content   = $converter->convertToHtml($content);
        }

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
        $result = Cache::has('help.' . $route . '.' . $language);
        if ($result) {
            Log::debug(sprintf('Cache has this entry: %s', 'help.' . $route . '.' . $language));
        }
        if (!$result) {
            Log::debug(sprintf('Cache does not have this entry: %s', 'help.' . $route . '.' . $language));
        }

        return $result;

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
        $key = 'help.' . $route . '.' . $language;
        Log::debug(sprintf('Will store entry in cache: %s', $key));
        Cache::put($key, $content, 10080); // a week.
    }
}
