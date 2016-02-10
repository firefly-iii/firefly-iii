<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use Config;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Route;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * @codeCoverageIgnore
 *
 * Class TwigSupport
 *
 * @package FireflyIII\Support
 */
class General extends Twig_Extension
{


    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            $this->formatAmount(),
            $this->formatTransaction(),
            $this->formatAmountPlain(),
            $this->formatJournal(),
            $this->balance(),
            $this->getAccountRole(),
            $this->formatFilesize(),
            $this->mimeIcon(),
        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->getCurrencyCode(),
            $this->getCurrencySymbol(),
            $this->phpdate(),
            $this->env(),

            $this->activeRouteStrict(),
            $this->activeRoutePartial(),
            $this->activeRoutePartialWhat(),
        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'FireflyIII\Support\Twig\General';
    }

    /**
     * Will return "active" when a part of the route matches the argument.
     * ie. "accounts" will match "accounts.index".
     *
     * @return Twig_SimpleFunction
     */
    protected function activeRoutePartial(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'activeRoutePartial', function () : string {
            $args  = func_get_args();
            $route = $args[0]; // name of the route.
            if (!(strpos(Route::getCurrentRoute()->getName(), $route) === false)) {
                return 'active';
            }

            return '';
        }
        );
    }

    /**
     * This function will return "active" when the current route matches the first argument (even partly)
     * but, the variable $what has been set and matches the second argument.
     *
     * @return Twig_SimpleFunction
     */
    protected function activeRoutePartialWhat(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'activeRoutePartialWhat', function ($context) : string {
            $args       = func_get_args();
            $route      = $args[1]; // name of the route.
            $what       = $args[2]; // name of the route.
            $activeWhat = $context['what'] ?? false;

            if ($what == $activeWhat && !(strpos(Route::getCurrentRoute()->getName(), $route) === false)) {
                return 'active';
            }

            return '';
        }, ['needs_context' => true]
        );
    }

    /**
     * Will return "active" when the current route matches the given argument
     * exactly.
     *
     * @return Twig_SimpleFunction
     */
    protected function activeRouteStrict(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'activeRouteStrict', function () : string {
            $args  = func_get_args();
            $route = $args[0]; // name of the route.

            if (Route::getCurrentRoute()->getName() == $route) {
                return 'active';
            }

            return '';
        }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function balance(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'balance', function (Account $account = null) : string {
            if (is_null($account)) {
                return 'NULL';
            }
            $date = session('end', Carbon::now()->endOfMonth());

            return app('steam')->balance($account, $date);
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function env(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'env', function (string $name, string $default) : string {
            return env($name, $default);
        }
        );
    }

    /**
     *
     * @return Twig_SimpleFilter
     */
    protected function formatAmount(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmount', function (string $string) : string {

            return app('amount')->format($string);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatAmountPlain(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatAmountPlain', function (string $string) : string {

            return app('amount')->format($string, false);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatFilesize(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'filesize', function (int $size) : string {

            // less than one GB, more than one MB
            if ($size < (1024 * 1024 * 2014) && $size >= (1024 * 1024)) {
                return round($size / (1024 * 1024), 2) . ' MB';
            }

            // less than one MB
            if ($size < (1024 * 1024)) {
                return round($size / 1024, 2) . ' KB';
            }

            return $size . ' bytes';
        }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatJournal(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatJournal', function (TransactionJournal $journal) : string {
            return app('amount')->formatJournal($journal);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatTransaction(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'formatTransaction', function (Transaction $transaction) : string {
            return app('amount')->formatTransaction($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function getAccountRole(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'getAccountRole', function (string $name) : string {
            return Config::get('firefly.accountRoles.' . $name);
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function getCurrencyCode(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'getCurrencyCode', function () : string {
            return app('amount')->getCurrencyCode();
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function getCurrencySymbol(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'getCurrencySymbol', function () : string {
            return app('amount')->getCurrencySymbol();
        }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function mimeIcon(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'mimeIcon', function (string $string) : string {
            switch ($string) {
                default:
                    return 'fa-file-o';
                case 'application/pdf':
                    return 'fa-file-pdf-o';
                case 'image/png':
                case 'image/jpeg':
                    return 'fa-file-image-o';
            }
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function phpdate()
    {
        return new Twig_SimpleFunction(
            'phpdate', function (string $str) : string {
            return date($str);
        }
        );
    }

}
