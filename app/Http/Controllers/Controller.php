<?php namespace FireflyIII\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
abstract class Controller extends BaseController
{

    use DispatchesCommands, ValidatesRequests;

}
