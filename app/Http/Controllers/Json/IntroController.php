<?php
/**
 * IntroController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class IntroController.
 */
class IntroController
{
    use GetConfigurationData;

    /**
     * Returns the introduction wizard for a page.
     *
     * @param string      $route
     * @param string|null $specificPage
     *
     * @return JsonResponse
     */
    public function getIntroSteps(string $route, string $specificPage = null): JsonResponse
    {
        Log::debug(sprintf('getIntroSteps for route "%s" and page "%s"', $route, $specificPage));
        $specificPage  = $specificPage ?? '';
        $steps         = $this->getBasicSteps($route);
        $specificSteps = $this->getSpecificSteps($route, $specificPage);
        if (0 === count($specificSteps)) {
            Log::debug(sprintf('No specific steps for route "%s" and page "%s"', $route, $specificPage));

            return response()->json($steps);
        }
        if ($this->hasOutroStep($route)) {
            // @codeCoverageIgnoreStart
            // save last step:
            $lastStep = $steps[count($steps) - 1];
            // remove last step:
            array_pop($steps);
            // merge arrays and add last step again
            $steps   = array_merge($steps, $specificSteps);
            $steps[] = $lastStep;
            // @codeCoverageIgnoreEnd
        }
        if (!$this->hasOutroStep($route)) {
            $steps = array_merge($steps, $specificSteps);
        }

        return response()->json($steps);
    }

    /**
     * Returns true if there is a general outro step.
     *
     * @param string $route
     *
     * @return bool
     */
    public function hasOutroStep(string $route): bool
    {
        $routeKey = str_replace('.', '_', $route);
        Log::debug(sprintf('Has outro step for route %s', $routeKey));
        $elements = config(sprintf('intro.%s', $routeKey));
        if (!is_array($elements)) {
            return false;
        }

        $hasStep = array_key_exists('outro', $elements);

        Log::debug('Elements is array', $elements);
        Log::debug('Keys is', array_keys($elements));
        Log::debug(sprintf('Keys has "outro": %s', var_export($hasStep, true)));

        return $hasStep;
    }

    /**
     * Enable the boxes for a specific page again.
     *
     * @param string      $route
     * @param string|null $specialPage
     *
     * @return JsonResponse
     */
    public function postEnable(string $route, string $specialPage = null): JsonResponse
    {
        $specialPage = $specialPage ?? '';
        $route       = str_replace('.', '_', $route);
        $key         = 'shown_demo_' . $route;
        if ('' !== $specialPage) {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as NOT done: %s with special "%s" (%s)', $route, $specialPage, $key));
        app('preferences')->set($key, false);
        app('preferences')->mark();

        return response()->json(['message' => (string)trans('firefly.intro_boxes_after_refresh')]);
    }

    /**
     * Set that you saw them.
     *
     * @param string      $route
     * @param string|null $specialPage
     *
     * @return JsonResponse
     */
    public function postFinished(string $route, string $specialPage = null): JsonResponse
    {
        $specialPage = $specialPage ?? '';
        $key         = 'shown_demo_' . $route;
        if ('' !== $specialPage) {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as done: %s with special "%s" (%s)', $route, $specialPage, $key));
        app('preferences')->set($key, true);

        return response()->json(['result' => sprintf('Reported demo watched for route "%s" (%s): %s.', $route, $specialPage, $key)]);
    }

}
