<?php
/**
 * SearchController.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

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
                app('view')->share('title', (string)trans('firefly.search'));

                return $next($request);
            }
        );
    }

    /**
     * Do the search.
     *
     * @param Request         $request
     * @param SearchInterface $searcher
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, SearchInterface $searcher)
    {
        $fullQuery = (string)$request->get('search');
        $page      = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        // parse search terms:
        $searcher->parseQuery($fullQuery);
        $query     = $searcher->getWordsAsString();
        $modifiers = $searcher->getModifiers();
        $subTitle  = (string)trans('breadcrumbs.search_result', ['query' => $query]);

        return view('search.index', compact('query', 'modifiers', 'page','fullQuery', 'subTitle'));
    }

    /**
     * JSON request that does the work.
     *
     * @param Request         $request
     * @param SearchInterface $searcher
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, SearchInterface $searcher): JsonResponse
    {
        $fullQuery = (string)$request->get('query');
        $page      = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');

        $searcher->parseQuery($fullQuery);
        $searcher->setPage($page);
        $searcher->setLimit((int)config('firefly.search_result_limit'));
        $groups     = $searcher->searchTransactions();
        $hasPages   = $groups->hasPages();
        $searchTime = round($searcher->searchTime(), 3); // in seconds
        $parameters = ['search' => $fullQuery];
        $url        = route('search.index') . '?' . http_build_query($parameters);
        $groups->setPath($url);
        try {
            $html = view('search.search', compact('groups', 'hasPages', 'searchTime'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render search.search: %s', $e->getMessage()));
            $html = 'Could not render view.';
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['count' => $groups->count(), 'html' => $html]);
    }
}
