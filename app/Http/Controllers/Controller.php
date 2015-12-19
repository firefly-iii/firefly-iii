<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Config;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Preferences;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
abstract class Controller extends BaseController
{

    use DispatchesJobs, ValidatesRequests;

    /** @var string */
    protected $monthAndDayFormat;
    /** @var string */
    protected $monthFormat;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);

        if (Auth::check()) {
            $pref                    = Preferences::get('language',env('DEFAULT_LANGUAGE','en_US'));
            $lang                    = $pref->data;
            $this->monthFormat       = Config::get('firefly.month.' . $lang);
            $this->monthAndDayFormat = Config::get('firefly.monthAndDay.' . $lang);

            View::share('monthFormat', $this->monthFormat);
            View::share('monthAndDayFormat', $this->monthAndDayFormat);
            View::share('language', $lang);
        }
    }
}
