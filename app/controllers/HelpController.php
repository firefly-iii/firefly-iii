<?php

/**
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 *
 * Class HelpController
 */
class HelpController extends BaseController
{
    /**
     * @param $route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($route)
    {
        $helpText  = '<p>There is no help for this route!</p>';
        $helpTitle = 'Help';
        if (!Route::has($route)) {
            \Log::error('No such route: ' . $route);

            return Response::json(['title' => $helpTitle, 'text' => $helpText]);
        }
        if (Cache::has('help.' . $route . '.title') && Cache::has('help.' . $route . '.text')) {
            $helpText  = Cache::get('help.' . $route . '.text');
            $helpTitle = Cache::get('help.' . $route . '.title');

            return Response::json(['title' => $helpTitle, 'text' => $helpText]);
        }

        $uri = 'https://raw.githubusercontent.com/JC5/firefly-iii-help/master/' . e($route) . '.md';
        \Log::debug('URL is: ' . $uri);
        try {
            $helpText = file_get_contents($uri);
        } catch (ErrorException $e) {
            \Log::error(trim($e->getMessage()));
        }
        \Log::debug('Found help for ' . $route);
        \Log::debug('Help text length for route ' . $route . ' is ' . strlen($helpText));
        \Log::debug('Help text IS: "' . $helpText . '".');
        if (strlen(trim($helpText)) == 0) {
            $helpText = '<p>There is no help for this route.</p>';
        }

        $helpText  = \Michelf\Markdown::defaultTransform($helpText);
        $helpTitle = $route;

        Cache::put('help.' . $route . '.text', $helpText, 10080); // a week.
        Cache::put('help.' . $route . '.title', $helpTitle, 10080);

        return Response::json(['title' => $helpTitle, 'text' => $helpText]);

    }
} 
