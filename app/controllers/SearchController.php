<?php

/**
 * Class SearchController
 */
class SearchController extends BaseController
{
    /**
     * Results always come in the form of an array [results, count, fullCount]
     */
    public function index()
    {

        /** @var \FireflyIII\Search\Search $searcher */
        $searcher = App::make('FireflyIII\Search\Search');

        $subTitle = null;
        $rawQuery = null;
        $result   = [];
        if (!is_null(Input::get('q')) && strlen(Input::get('q')) > 0) {
            $rawQuery = trim(Input::get('q'));
            $words    = explode(' ', $rawQuery);
            $subTitle = 'Results for "' . e($rawQuery) . '"';

            $transactions = $searcher->searchTransactions($words);
            $accounts     = $searcher->searchAccounts($words);
            $categories   = $searcher->searchCategories($words);
            $budgets      = $searcher->searchBudgets($words);
            $tags         = $searcher->searchTags($words);
            $result       = ['transactions' => $transactions, 'accounts' => $accounts, 'categories' => $categories, 'budgets' => $budgets, 'tags' => $tags];

        }

        return View::make('search.index')->with('title', 'Search')->with('subTitle', $subTitle)->with(
            'mainTitleIcon', 'fa-search'
        )->with('query', $rawQuery)->with('result', $result);
    }
}
