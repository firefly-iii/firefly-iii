<?php

/**
 * Class JsonController
 *
 */
class JsonController extends BaseController
{

    /**
     * Returns a list of categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        /** @var \FireflyIII\Database\Category $categories */
        $categories = App::make('FireflyIII\Database\Category');
        $list       = $categories->get();
        $return     = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);


    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts()
    {
        /** @var \FireflyIII\Database\Account $accounts */
        $accounts = App::make('FireflyIII\Database\Account');
        $list     = $accounts->getExpenseAccounts();
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts()
    {
        /** @var \FireflyIII\Database\Account $accounts */
        $accounts = App::make('FireflyIII\Database\Account');
        $list     = $accounts->getRevenueAccounts();
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }
}