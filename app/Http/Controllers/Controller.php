<?php

namespace FireflyIII\Http\Controllers;

use App;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use NumberFormatter;
use Preferences;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var string|\Symfony\Component\Translation\TranslatorInterface */
    protected $monthAndDayFormat;
    /** @var string|\Symfony\Component\Translation\TranslatorInterface */
    protected $monthFormat;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);

        if (Auth::check()) {
            $pref                    = Preferences::get('language', env('DEFAULT_LANGUAGE', 'en_US'));
            $lang                    = $pref->data;
            $this->monthFormat       = trans('config.month');
            $this->monthAndDayFormat = trans('config.month_and_day');

            App::setLocale($lang);
            Carbon::setLocale(substr($lang, 0, 2));
            $locale = explode(',', trans('config.locale'));
            $locale = array_map('trim', $locale);

            setlocale(LC_TIME, $locale);
            setlocale(LC_MONETARY, $locale);

            // change localeconv to a new array:
            $numberFormatter = numfmt_create($lang, NumberFormatter::CURRENCY);
            $localeconv      = [
                'mon_decimal_point' => $numberFormatter->getSymbol($numberFormatter->getAttribute(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL)),
                'mon_thousands_sep' => $numberFormatter->getSymbol($numberFormatter->getAttribute(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL)),
                'frac_digits'       => $numberFormatter->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS),
            ];
            View::share('monthFormat', $this->monthFormat);
            View::share('monthAndDayFormat', $this->monthAndDayFormat);
            View::share('language', $lang);
            View::share('localeconv', $localeconv);
        }
    }

    /**
     * Take the array as returned by SingleCategoryRepositoryInterface::spentPerDay and SingleCategoryRepositoryInterface::earnedByDay
     * and sum up everything in the array in the given range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array  $array
     *
     * @return string
     */
    protected function getSumOfRange(Carbon $start, Carbon $end, array $array)
    {
        bcscale(2);
        $sum          = '0';
        $currentStart = clone $start; // to not mess with the original one
        $currentEnd   = clone $end; // to not mess with the original one

        while ($currentStart <= $currentEnd) {
            $date = $currentStart->format('Y-m-d');
            if (isset($array[$date])) {
                $sum = bcadd($sum, $array[$date]);
            }
            $currentStart->addDay();
        }

        return $sum;
    }


}
