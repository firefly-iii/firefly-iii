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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Http\Request;

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

    }

    /**
     * Results always come in the form of an array [results, count, fullCount]
     *
     * @param Request         $request
     * @param SearchInterface $searcher
     *
     * @return $this
     */
    public function index(Request $request, SearchInterface $searcher)
    {
        $minSearchLen  = 1;
        $subTitle      = null;
        $query         = null;
        $result        = [];
        $title         = trans('firefly.search');
        $limit         = 20;
        $mainTitleIcon = 'fa-search';

        // set limit for search:
        $searcher->setLimit($limit);

        if (!is_null($request->get('q')) && strlen($request->get('q')) >= $minSearchLen) {
            $query    = trim(strtolower($request->get('q')));
            $words    = explode(' ', $query);
            $subTitle = trans('firefly.search_results_for', ['query' => $query]);

            $transactions = $searcher->searchTransactions($words);
            $accounts     = $searcher->searchAccounts($words);
            $categories   = $searcher->searchCategories($words);
            $budgets      = $searcher->searchBudgets($words);
            $tags         = $searcher->searchTags($words);
            $result       = ['transactions' => $transactions, 'accounts' => $accounts, 'categories' => $categories, 'budgets' => $budgets, 'tags' => $tags];

        }

        return view('search.index', compact('title', 'subTitle', 'limit', 'mainTitleIcon', 'query', 'result'));
    }

}
