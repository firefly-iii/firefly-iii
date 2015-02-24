<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests;
use FireflyIII\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Response;
use Auth;

/**
 * Class JsonController
 *
 * @package FireflyIII\Http\Controllers
 */
class JsonController extends Controller {


    /**
     * Returns a list of categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $list       = Auth::user()->categories()->orderBy('name','ASC')->get();
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
        $list     = Auth::user()->accounts()->accountTypeIn(['Expense account', 'Beneficiary account'])->get();
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
        $list     = Auth::user()->accounts()->accountTypeIn(['Revenue account'])->get();
        $return   = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

}
