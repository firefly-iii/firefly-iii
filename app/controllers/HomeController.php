<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class HomeController extends BaseController {

    public function __construct(ARI $accounts) {
        $this->accounts = $accounts;
    }

	public function index()
	{

        $count = $this->accounts->count();
        if($count == 0) {
            return Redirect::route('start');
        }
		return View::make('index');
	}

    public function start() {
        return View::make('start');
    }

}
