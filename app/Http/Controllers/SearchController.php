<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Input;

/**
 * Class SearchController
 *
 * @package FireflyIII\Http\Controllers
 */
class SearchController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Results always come in the form of an array [results, count, fullCount]
     *
     * @param SearchInterface $searcher
     *
     * @return $this
     */
    public function index(SearchInterface $searcher)
    {

        $subTitle = null;
        $rawQuery = null;
        $result   = [];
        if (!is_null(Input::get('q')) && strlen(Input::get('q')) > 0) {
            $rawQuery = trim(Input::get('q'));
            $words    = explode(' ', $rawQuery);
            $subTitle = trans('firefly.search_results_for', ['query' => $rawQuery]);

            $transactions = $searcher->searchTransactions($words);
            $accounts     = $searcher->searchAccounts($words);
            $categories   = $searcher->searchCategories($words);
            $budgets      = $searcher->searchBudgets($words);
            $tags         = $searcher->searchTags($words);
            $result       = ['transactions' => $transactions, 'accounts' => $accounts, 'categories' => $categories, 'budgets' => $budgets, 'tags' => $tags];

        }

        return view('search.index')->with('title', 'Search')->with('subTitle', $subTitle)->with(
            'mainTitleIcon', 'fa-search'
        )->with('query', $rawQuery)->with('result', $result);
    }

}
