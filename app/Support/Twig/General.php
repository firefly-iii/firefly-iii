<?php

namespace FireflyIII\Support\Twig;

use App;
use Carbon\Carbon;
use Config;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Route;
use Session;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class TwigSupport
 *
 * @package FireflyIII\Support
 */
class General extends Twig_Extension
{


    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getFilters()
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            'formatAmount', function ($string) {
            return App::make('amount')->format($string);
        }, ['is_safe' => ['html']]
        );

        $filters[] = new Twig_SimpleFilter(
            'formatTransaction', function (Transaction $transaction) {
            return App::make('amount')->formatTransaction($transaction);
        }, ['is_safe' => ['html']]
        );

        $filters[] = new Twig_SimpleFilter(
            'formatAmountPlain', function ($string) {
            return App::make('amount')->format($string, false);
        }, ['is_safe' => ['html']]
        );

        $filters[] = new Twig_SimpleFilter(
            'formatJournal', function ($journal) {
            return App::make('amount')->formatJournal($journal);
        }, ['is_safe' => ['html']]
        );

        $filters[] = new Twig_SimpleFilter(
            'balance', function (Account $account = null) {
            if (is_null($account)) {
                return 'NULL';
            }
            $date = Session::get('end', Carbon::now()->endOfMonth());

            return App::make('steam')->balance($account, $date);
        }
        );

        // should be a function but OK
        $filters[] = new Twig_SimpleFilter(
            'getAccountRole', function ($name) {
            return Config::get('firefly.accountRoles.' . $name);
        }
        );

        return $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        $functions = [];

        $functions[] = new Twig_SimpleFunction(
            'getCurrencyCode', function () {
            return App::make('amount')->getCurrencyCode();
        }
        );

        $functions[] = new Twig_SimpleFunction(
            'getCurrencySymbol', function () {
            return App::make('amount')->getCurrencySymbol();
        }
        );

        $functions[] = new Twig_SimpleFunction(
            'phpdate', function ($str) {
            return date($str);
        }
        );


        $functions[] = new Twig_SimpleFunction(
            'env', function ($name, $default) {
            return env($name, $default);
        }
        );

        $functions[] = new Twig_SimpleFunction(
            'activeRoute', function ($context) {
            $args       = func_get_args();
            $route      = $args[1];
            $what       = isset($args[2]) ? $args[2] : false;
            $strict     = isset($args[3]) ? $args[3] : false;
            $activeWhat = isset($context['what']) ? $context['what'] : false;

            // activeRoute
            if (!($what === false)) {
                if ($what == $activeWhat && Route::getCurrentRoute()->getName() == $route) {
                    return 'active because-active-what';
                }
            } else {
                if (!$strict && !(strpos(Route::getCurrentRoute()->getName(), $route) === false)) {
                    return 'active because-route-matches-non-strict';
                } else {
                    if ($strict && Route::getCurrentRoute()->getName() == $route) {
                        return 'active because-route-matches-strict';
                    }
                }
            }

            return 'not-xxx-at-all';
        }, ['needs_context' => true]
        );

        return $functions;


    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\General';
    }

}
