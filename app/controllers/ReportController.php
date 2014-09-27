<?php

/**
 * Class ReportController
 */
class ReportController extends BaseController
{


    /**
     *
     */
    public function index()
    {
        return View::make('reports.index')->with('title','Reports')->with('mainTitleIcon','fa-line-chart');
    }

}