<?php

class RepeatedExpenseController extends BaseController
{
    public function __construct() {
        View::share('title','Repeated expenses');
        View::share('mainTitleIcon','fa-rotate-left');
    }
    public function index()
    {

        $subTitle = 'Overview';
        return View::make('repeatedexpense.index',compact('subTitle'));
    }
} 