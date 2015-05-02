<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use Log;
use Response;

/**
 * Class HelpController
 *
 * @package FireflyIII\Http\Controllers
 */
class HelpController extends Controller
{

    /**
     * @param $route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($route, HelpInterface $help)
    {
        $content = [
            'text'  => '<p>There is no help for this route!</p>',
            'title' => 'Help',
        ];

        if (!$help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return Response::json($content);
        }

        if ($help->inCache($route)) {
            $content = [
                'text'  => $help->getFromCache('help.' . $route . '.text'),
                'title' => $help->getFromCache('help.' . $route . '.title'),
            ];

            return Response::json($content);
        }
        $content = $help->getFromGithub($route);

        $help->putInCache($route, $content);

        return Response::json($content);

    }


}
