<?php namespace FireflyIII\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
abstract class Controller extends BaseController
{

    use DispatchesCommands, ValidatesRequests;

    /**
     *
     */
    public function __construct()
    {
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);
    }
}
