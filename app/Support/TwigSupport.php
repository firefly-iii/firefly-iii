<?php

namespace FireflyIII\Support;

use App;
use FireflyIII\Models\Account;
use Route;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class TwigSupport
 *
 * @package FireflyIII\Support
 */
class TwigSupport extends Twig_Extension
{


    public function getFilters()
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            'formatAmount', function ($string) {
            return App::make('amount')->format($string);
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

            return App::make('amount')->format(App::make('steam')->balance($account));
        }, ['is_safe' => ['html']]
        );
        $filters[]  = new Twig_SimpleFilter(
            'activeRoute', function ($string) {
            if (Route::getCurrentRoute()->getName() == $string) {
                return 'active';
            }

            return '';
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
            'env', function ($name, $default) {
            return env($name, $default);
        }
        );

        return $functions;


    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'FireflyIII\Support\TwigSupport';
    }

}