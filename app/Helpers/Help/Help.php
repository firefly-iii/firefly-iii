<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Help;

use Cache;
use League\CommonMark\CommonMarkConverter;
use Log;
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
     *
     * @param string $key
     *
     * @return string
     */
    public function getFromCache(string $key): string
    {
        return Cache::get($key);
    }

    /**
     * @param string $language
     * @param string $route
     *
     * @return array
     */
    public function getFromGithub(string $language, string $route): array
    {

        $uri        = sprintf('https://raw.githubusercontent.com/JC5/firefly-iii-help/master/%s/%s.md', $language, $route);
        $routeIndex = str_replace('.', '-', $route);
        $title      = trans('help.' . $routeIndex);
        $content    = [
            'text'  => '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>',
            'title' => $title,
        ];

        Log::debug('Going to get from Github: ' . $uri);

        $result = Requests::get($uri);

        Log::debug('Status code was ' . $result->status_code . '.');

        if ($result->status_code === 200) {
            $content['text'] = $result->body;
        }


        if (strlen(trim($content['text'])) == 0) {
            Log::debug('No actual help text for this route (even though a page was found).');
            $content['text'] = '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>';
        }
        $converter       = new CommonMarkConverter();
        $content['text'] = $converter->convertToHtml($content['text']);

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
     *
     * @param string $route
     *
     * @return bool
     */
    public function inCache(string $route):bool
    {
        return Cache::has('help.' . $route . '.title') && Cache::has('help.' . $route . '.text');
    }

    /**
     *
     * @param string $route
     * @param string $language
     * @param array  $content
     *
     * @internal param $title
     */
    public function putInCache(string $route, string $language, array $content)
    {
        Cache::put('help.' . $route . '.text.' . $language, $content['text'], 10080); // a week.
        Cache::put('help.' . $route . '.title.' . $language, $content['title'], 10080);
    }
}
