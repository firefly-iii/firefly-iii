<?php

use Firefly\Helper\Controllers\SearchInterface as SI;

/**
 * Class SearchController
 */
class SearchController extends BaseController
{
    protected $_helper;

    public function __construct(SI $helper)
    {
        $this->_helper = $helper;

    }

    /**
     * Results always come in the form of an array [results, count, fullCount]
     */
    public function index()
    {
        $subTitle = null;
        if (!is_null(Input::get('q'))) {
            $rawQuery = trim(Input::get('q'));
            $words    = explode(' ', $rawQuery);
            $subTitle = 'Results for "' . e($rawQuery) . '"';

            /*
             * Search for transactions:
             */
            $result = $this->_helper->transactions($words);

        }

        return View::make('search.index')->with('title', 'Search')->with('subTitle', $subTitle)->with(
            'mainTitleIcon', 'fa-search'
        );
    }
}