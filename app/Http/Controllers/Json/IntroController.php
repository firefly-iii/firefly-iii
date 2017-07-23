<?php
/**
 * IntroController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Support\Facades\Preferences;
use Log;
use Response;

/**
 * Class IntroController
 *
 * @package FireflyIII\Http\Controllers\Json
 */
class IntroController
{

    /**
     * @param string $route
     * @param string $specificPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntroSteps(string $route, string $specificPage = '')
    {
        $steps         = $this->getBasicSteps($route);
        $specificSteps = $this->getSpecificSteps($route, $specificPage);
        if (count($specificSteps) === 0) {
            return Response::json($steps);
        }
        if ($this->hasOutroStep($route)) {
            // save last step:
            $lastStep = $steps[count($steps) - 1];
            // remove last step:
            array_pop($steps);
            // merge arrays and add last step again
            $steps   = array_merge($steps, $specificSteps);
            $steps[] = $lastStep;

        }
        if (!$this->hasOutroStep($route)) {
            $steps = array_merge($steps, $specificSteps);
        }

        return Response::json($steps);
    }

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasOutroStep(string $route): bool
    {
        $routeKey = str_replace('.', '_', $route);
        $elements = config(sprintf('intro.%s', $routeKey));
        if (!is_array($elements)) {
            return false;
        }
        $keys = array_keys($elements);

        return in_array('outro', $keys);
    }

    /**
     * @param string $route
     * @param string $specialPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postEnable(string $route, string $specialPage = '')
    {
        $route = str_replace('.', '_', $route);
        $key   = 'shown_demo_' . $route;
        if ($specialPage !== '') {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as NOT done: %s with special "%s" (%s)', $route, $specialPage, $key));
        Preferences::set($key, false);

        return Response::json(['message' => trans('firefly.intro_boxes_after_refresh')]);
    }

    /**
     * @param string $route
     * @param string $specialPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postFinished(string $route, string $specialPage = '')
    {
        $key = 'shown_demo_' . $route;
        if ($specialPage !== '') {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as done: %s with special "%s" (%s)', $route, $specialPage, $key));
        Preferences::set($key, true);

        return Response::json(['result' => sprintf('Reported demo watched for route "%s".', $route)]);
    }

    /**
     * @param string $route
     *
     * @return array
     */
    private function getBasicSteps(string $route): array
    {
        $routeKey = str_replace('.', '_', $route);
        $elements = config(sprintf('intro.%s', $routeKey));
        $steps    = [];
        if (is_array($elements) && count($elements) > 0) {
            foreach ($elements as $key => $options) {
                $currentStep = $options;

                // get the text:
                $currentStep['intro'] = trans('intro.' . $route . '_' . $key);


                // save in array:
                $steps[] = $currentStep;
            }
        }

        return $steps;
    }

    /**
     * @param string $route
     * @param string $specificPage
     *
     * @return array
     */
    private function getSpecificSteps(string $route, string $specificPage): array
    {
        $steps = [];

        // user is on page with specific instructions:
        if (strlen($specificPage) > 0) {
            $routeKey = str_replace('.', '_', $route);
            $elements = config(sprintf('intro.%s', $routeKey . '_' . $specificPage));
            if (is_array($elements) && count($elements) > 0) {
                foreach ($elements as $key => $options) {
                    $currentStep = $options;

                    // get the text:
                    $currentStep['intro'] = trans('intro.' . $route . '_' . $specificPage . '_' . $key);

                    // save in array:
                    $steps[] = $currentStep;
                }
            }
        }

        return $steps;
    }

}