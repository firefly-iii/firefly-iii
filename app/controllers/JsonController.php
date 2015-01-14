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
        /** @var \FireflyIII\Database\Category\Category $categories */
        $categories = App::make('FireflyIII\Database\Category\Category');
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
        /** @var \FireflyIII\Database\Account\Account $accounts */
        $accounts = App::make('FireflyIII\Database\Account\Account');
        $list     = $accounts->getAccountsByType(['Expense account', 'Beneficiary account']);
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
        /** @var \FireflyIII\Database\Account\Account $accounts */
        $accounts = App::make('FireflyIII\Database\Account\Account');
        $list     = $accounts->getAccountsByType(['Revenue account']);
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }
}
