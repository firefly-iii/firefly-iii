<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class HomeController extends BaseController {

    public function __construct(ARI $accounts) {
        $this->accounts = $accounts;
    }

	public function index()
	{
		return View::make('index');
	}
}
