<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class AccountController extends \BaseController
{

    public function __construct(ARI $accounts)
    {
        $this->accounts = $accounts;

        View::share('menu', 'accounts');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $all = $this->accounts->get();
        $list = [
            'personal'      => [],
            'beneficiaries' => [],
            'initial'       => [],
            'cash'          => []
        ];

        foreach ($all as $account) {
            switch ($account->accounttype->description) {
                case 'Default account':
                    $list['personal'][] = $account;
                    break;
                case 'Cash account':
                    $list['cash'][] = $account;
                    break;
                case 'Initial balance account':
                    $list['initial'][] = $account;
                    break;
                case 'Beneficiary account':
                    $list['beneficiaries'][] = $account;
                    break;

            }
        }

        return View::make('accounts.index')->with('accounts', $list);
    }
//
//
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return View::make('accounts.create');
    }
//
//
//	/**
//	 * Store a newly created resource in storage.
//	 *
//	 * @return Response
//	 */
//	public function store()
//	{
//        $account = $this->accounts->store();
//        if($account === false) {
//            Session::flash('error','Could not create account with provided information');
//            return Redirect::route('accounts.create')->withInput()->withErrors($this->accounts->validator);
//        }
//	}
//
//
    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {

    }
//
//
//	/**
//	 * Show the form for editing the specified resource.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function edit($id)
//	{
//		//
//	}
//
//
//	/**
//	 * Update the specified resource in storage.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function update($id)
//	{
//		//
//	}
//
//
//	/**
//	 * Remove the specified resource from storage.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function destroy($id)
//	{
//		//
//	}


}
