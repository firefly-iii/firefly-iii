<?php
use Carbon\Carbon as Carbon;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class HomeController extends BaseController {

    public function __construct(ARI $accounts) {
        $this->accounts = $accounts;
    }

	public function index()
	{
        $count = $this->accounts->count();
		return View::make('index')->with('count',$count);
	}
}
