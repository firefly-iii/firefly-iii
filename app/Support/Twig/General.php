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
        return [
            $this->formatAmount(),
            $this->formatTransaction(),
            $this->formatAmountPlain(),
            $this->formatJournal(),
            $this->balance(),
            $this->getAccountRole()
        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            $this->getCurrencyCode(),
            $this->getCurrencySymbol(),
            $this->phpdate(),
            $this->env(),
            $this->activeRoute()
        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\General';
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmount()
    {
        return new Twig_SimpleFilter(
            'formatAmount', function ($string) {
            return App::make('amount')->format($string);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatTransaction()
    {
        return new Twig_SimpleFilter(
            'formatTransaction', function (Transaction $transaction) {
            return App::make('amount')->formatTransaction($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmountPlain()
    {
        return new Twig_SimpleFilter(
            'formatAmountPlain', function ($string) {
            return App::make('amount')->format($string, false);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatJournal()
    {
        return new Twig_SimpleFilter(
            'formatJournal', function ($journal) {
            return App::make('amount')->formatJournal($journal);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function balance()
    {
        return new Twig_SimpleFilter(
            'balance', function (Account $account = null) {
            if (is_null($account)) {
                return 'NULL';
            }
            $date = Session::get('end', Carbon::now()->endOfMonth());

            return App::make('steam')->balance($account, $date);
        }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function getAccountRole()
    {
        return new Twig_SimpleFilter(
            'getAccountRole', function ($name) {
            return Config::get('firefly.accountRoles.' . $name);
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function getCurrencyCode()
    {
        return new Twig_SimpleFunction(
            'getCurrencyCode', function () {
            return App::make('amount')->getCurrencyCode();
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function getCurrencySymbol()
    {
        return new Twig_SimpleFunction(
            'getCurrencySymbol', function () {
            return App::make('amount')->getCurrencySymbol();
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function phpdate()
    {
        return new Twig_SimpleFunction(
            'phpdate', function ($str) {
            return date($str);
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function env()
    {
        return new Twig_SimpleFunction(
            'env', function ($name, $default) {
            return env($name, $default);
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function activeRoute()
    {
        return new Twig_SimpleFunction(
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
    }

}
