<?php
/**
 * JsonController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\Request;
use Response;

/**
 * Class JsonController
 *
 * @package FireflyIII\Http\Controllers
 */
class JsonController extends Controller
{
    /**
     * JsonController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request)
    {
        $count   = intval($request->get('count')) > 0 ? intval($request->get('count')) : 1;
        $keys    = array_keys(config('firefly.rule-actions'));
        $actions = [];
        foreach ($keys as $key) {
            $actions[$key] = trans('firefly.rule_action_' . $key . '_choice');
        }
        $view = view('rules.partials.action', compact('actions', 'count'))->render();


        return Response::json(['html' => $view]);
    }

    /**
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgets(BudgetRepositoryInterface $repository)
    {
        $return = array_unique($repository->getBudgets()->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a list of categories.
     *
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(CategoryRepositoryInterface $repository)
    {
        $return = array_unique($repository->getCategories()->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param TagRepositoryInterface $tagRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(TagRepositoryInterface $tagRepository)
    {
        $return = array_unique($tagRepository->get()->pluck('tag')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @param JournalRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionTypes(JournalRepositoryInterface $repository)
    {
        $return = array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trigger(Request $request)
    {
        $count    = intval($request->get('count')) > 0 ? intval($request->get('count')) : 1;
        $keys     = array_keys(config('firefly.rule-triggers'));
        $triggers = [];
        foreach ($keys as $key) {
            if ($key !== 'user_action') {
                $triggers[$key] = trans('firefly.rule_trigger_' . $key . '_choice');
            }
        }
        asort($triggers);

        $view = view('rules.partials.trigger', compact('triggers', 'count'))->render();


        return Response::json(['html' => $view]);
    }
}
