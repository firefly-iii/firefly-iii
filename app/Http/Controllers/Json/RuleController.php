<?php
/**
 * RuleController.php
 * Copyright (c) 2019 james@firefly-iii.org
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


use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

/**
 * Class RuleController
 */
class RuleController extends Controller
{
    /**
     * Render HTML form for rule action.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function action(Request $request): JsonResponse
    {
        $count   = (int)$request->get('count') > 0 ? (int)$request->get('count') : 1;
        $keys    = array_keys(config('firefly.rule-actions'));
        $actions = [];
        foreach ($keys as $key) {
            $actions[$key] = (string)trans('firefly.rule_action_' . $key . '_choice');
        }
        try {
            $view = view('rules.partials.action', compact('actions', 'count'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render rules.partials.action: %s', $e->getMessage()));
            $view = 'Could not render view.';
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['html' => $view]);
    }

    /**
     * Render HTML for rule trigger.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function trigger(Request $request): JsonResponse
    {
        $count    = (int)$request->get('count') > 0 ? (int)$request->get('count') : 1;
        $keys     = array_keys(config('firefly.rule-triggers'));
        $triggers = [];
        foreach ($keys as $key) {
            if ('user_action' !== $key) {
                $triggers[$key] = (string)trans('firefly.rule_trigger_' . $key . '_choice');
            }
        }
        asort($triggers);

        try {
            $view = view('rules.partials.trigger', compact('triggers', 'count'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render rules.partials.trigger: %s', $e->getMessage()));
            $view = 'Could not render view.';
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['html' => $view]);
    }

}
