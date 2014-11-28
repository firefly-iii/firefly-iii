<?php

class HelpController extends BaseController
{
    public function show($route)
    {
        // no valid route
        if (!Route::has($route)) {
            $helpText  = '<p>There is no help for this route!</p>';
            $helpTitle = 'Help';

            return Response::json(['title' => $helpTitle, 'text' => $helpText]);
        }

        // content in cache
        if (Cache::has('help.' . $route . '.title') && Cache::has('help.' . $route . '.text')) {
            $helpText  = Cache::get('help.' . $route . '.text');
            $helpTitle = Cache::get('help.' . $route . '.title');

            return Response::json(['title' => $helpTitle, 'text' => $helpText]);
        }

        // get the help-content from Github:
        $URL = 'https://raw.githubusercontent.com/JC5/firefly-iii-help/master/' . e($route) . '.md';
        try {
            $content = file_get_contents($URL);
        } catch (ErrorException $e) {
            $content = '<p>There is no help for this route.</p>';
        }
        if (strlen($content) > 0) {
            $helpText  = \Michelf\Markdown::defaultTransform($content);
            $helpTitle = $route;

            Cache::put('help.' . $route . '.text', $helpText, 10080); // a week.
            Cache::put('help.' . $route . '.title', $helpTitle, 10080);

            return Response::json(['title' => $helpTitle, 'text' => $helpText]);
        }

        $helpText  = '<p>There is no help for this route!</p>';
        $helpTitle = 'Help';

        return Response::json(['title' => $helpTitle, 'text' => $helpText]);

    }
} 