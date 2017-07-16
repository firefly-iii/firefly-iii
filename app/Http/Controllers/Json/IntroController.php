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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntroSteps(string $route)
    {
        $route    = str_replace('.', '_', $route);
        $elements = config(sprintf('intro.%s', $route));
        $steps    = [];
        if (is_array($elements) && count($elements) > 0) {
            foreach ($elements as $key => $options) {
                $currentStep = $options;

                // point to HTML element when not an intro or outro:
                if (!in_array($key, ['intro', 'outro'])) {
                    $currentStep['element'] = $options['selector'];
                }

                // get the text:
                $currentStep['intro'] = trans('intro.' . $route . '_' . $key);



                // save in array:
                $steps[] = $currentStep;
            }
        }

        return Response::json($steps);
    }

    /**
     * @param string $route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postFinished(string $route)
    {
        $key = 'shown_demo_' . $route;

        // Preferences::set($key, true);

        return Response::json(['result' => sprintf('Reported demo watched for route "%s".', $route)]);
    }

}