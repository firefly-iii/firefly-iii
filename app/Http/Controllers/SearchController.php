<?php

/**
 * SearchController.php
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

namespace FireflyIII\Http\Controllers;

use Throwable;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class SearchController.
 */
class SearchController extends Controller
{
    /**
     * SearchController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showCategory', true);
        $this->middleware(
            static function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-search');
                app('view')->share('title', (string) trans('firefly.search'));

                return $next($request);
            }
        );
    }

    /**
     * Do the search.
     *
     * @return Factory|View
     */
    public function index(Request $request, SearchInterface $searcher)
    {
        // search params:
        $fullQuery        = $request->get('search');
        if (is_array($request->get('search'))) {
            $fullQuery = '';
        }
        $fullQuery        = (string) $fullQuery;
        $page             = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $ruleId           = (int) $request->get('rule');
        $ruleChanged      = false;

        // find rule, check if query is different, offer to update.
        $ruleRepository   = app(RuleRepositoryInterface::class);
        $rule             = $ruleRepository->find($ruleId);
        if (null !== $rule) {
            $originalQuery = $ruleRepository->getSearchQuery($rule);
            if ($originalQuery !== $fullQuery) {
                $ruleChanged = true;
            }
        }
        // parse search terms:
        $searcher->parseQuery($fullQuery);

        // words from query and operators:
        $words            = $searcher->getWords();
        $excludedWords    = $searcher->getExcludedWords();
        $operators        = $searcher->getOperators();
        $invalidOperators = $searcher->getInvalidOperators();
        $subTitle         = (string) trans('breadcrumbs.search_result', ['query' => $fullQuery]);

        return view('search.index', compact('words', 'excludedWords', 'operators', 'page', 'rule', 'fullQuery', 'subTitle', 'ruleId', 'ruleChanged', 'invalidOperators'));
    }

    /**
     * JSON request that does the work.
     *
     * @throws FireflyException
     */
    public function search(Request $request, SearchInterface $searcher): JsonResponse
    {
        $entry      = $request->get('query');
        if (!is_scalar($entry)) {
            $entry = '';
        }
        $fullQuery  = (string) $entry;
        $page       = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');

        $searcher->parseQuery($fullQuery);

        $searcher->setPage($page);
        $groups     = $searcher->searchTransactions();
        $hasPages   = $groups->hasPages();
        $searchTime = round($searcher->searchTime(), 3); // in seconds
        $parameters = ['search' => $fullQuery];
        $url        = route('search.index').'?'.http_build_query($parameters);
        $groups->setPath($url);

        try {
            $html = view('search.search', compact('groups', 'hasPages', 'searchTime'))->render();
        } catch (Throwable $e) {
            app('log')->error(sprintf('Cannot render search.search: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $html = 'Could not render view.';

            throw new FireflyException($html, 0, $e);
        }

        return response()->json(['count' => $groups->count(), 'html' => $html]);
    }
}
