<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/15
 * Time: 10:37
 */

namespace FireflyIII\Http\Controllers;
use View;

/**
 * Class CsvController
 *
 * @package FireflyIII\Http\Controllers
 */
class CsvController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', 'CSV');
        View::share('mainTitleIcon', 'fa-file-text-o');

    }

    public function index()
    {
        return view('csv.index');
    }
}