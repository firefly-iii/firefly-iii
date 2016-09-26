<?php
/**
 * HelpController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
    /**
     * HelpController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param HelpInterface $help
     * @param               $route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(HelpInterface $help, string $route)
    {
        $language = Preferences::get('language', config('firefly.default_language', 'en_US'))->data;
        $content  = [
            'text'  => '<p>' . strval(trans('firefly.route_has_no_help')) . '</p>',
            'title' => 'Help',
        ];

        if (!$help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return Response::json($content);
        }

        if ($help->inCache($route)) {
            $content = [
                'text'  => $help->getFromCache('help.' . $route . '.text.' . $language),
                'title' => $help->getFromCache('help.' . $route . '.title.' . $language),
            ];

            return Response::json($content);
        }

        $content = $help->getFromGithub($language, $route);

        $help->putInCache($route, $language, $content);

        return Response::json($content);

    }


}
