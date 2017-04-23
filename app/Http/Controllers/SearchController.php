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
use Illuminate\Support\Collection;
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
        // yes, hard coded values:
        $minSearchLen = 1;
        $limit        = 20;

        // ui stuff:
        $subTitle = '';

        // query stuff
        $query        = null;
        $result       = [];
        $rawQuery     = $request->get('q');
        $hasModifiers = true;
        $modifiers    = [];

        if (!is_null($request->get('q')) && strlen($request->get('q')) >= $minSearchLen) {
            // parse query, find modifiers:
            // set limit for search
            $searcher->setLimit($limit);
            $searcher->parseQuery($request->get('q'));

            $transactions = $searcher->searchTransactions();
            $accounts     = new Collection;
            $categories   = new Collection;
            $tags         = new Collection;
            $budgets      = new Collection;

            // no special search thing?
            if (!$searcher->hasModifiers()) {
                $hasModifiers = false;
                $accounts     = $searcher->searchAccounts();
                $categories   = $searcher->searchCategories();
                $budgets      = $searcher->searchBudgets();
                $tags         = $searcher->searchTags();
            }
            $query    = $searcher->getWordsAsString();
            $subTitle = trans('firefly.search_results_for', ['query' => $query]);
            $result   = ['transactions' => $transactions, 'accounts' => $accounts, 'categories' => $categories, 'budgets' => $budgets, 'tags' => $tags];

        }

        return view('search.index', compact('rawQuery', 'hasModifiers', 'modifiers', 'subTitle', 'limit', 'query', 'result'));
    }

}
