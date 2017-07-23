<?php
/**
 * SearchController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Http\Request;
use Response;
use View;

/**
 * Class SearchController
 *
 * @package FireflyIII\Http\Controllers
 */
class SearchController extends Controller
{
    /**
     * SearchController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-search');
                View::share('title', trans('firefly.search'));

                return $next($request);
            }
        );

    }

    /**
     * @param Request         $request
     * @param SearchInterface $searcher
     *
     * @return View
     */
    public function index(Request $request, SearchInterface $searcher)
    {
        $fullQuery = $request->get('q');

        // parse search terms:
        $searcher->parseQuery($fullQuery);
        $query    = $searcher->getWordsAsString();
        $subTitle = trans('breadcrumbs.search_result', ['query' => $query]);

        return view('search.index', compact('query', 'fullQuery', 'subTitle'));
    }

    public function search(Request $request, SearchInterface $searcher)
    {
        $fullQuery = $request->get('query');

        // parse search terms:
        $searcher->parseQuery($fullQuery);
        $searcher->setLimit(20);
        $transactions = $searcher->searchTransactions();
        $html         = view('search.search', compact('transactions'))->render();

        return Response::json(['count' => $transactions->count(), 'html' => $html]);


    }

}
