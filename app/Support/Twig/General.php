<?php
/**
 * General.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use Route;
use Steam;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
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
            $this->balance(),
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
            $this->getAmountFromJournal(),
            $this->activeRouteStrict(),
            $this->steamPositive(),
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
            'activeRoutePartial',
            function (): string {
                $args  = func_get_args();
                $route = $args[0]; // name of the route.
                $name  = Route::getCurrentRoute()->getName() ?? '';
                if (!(strpos($name, $route) === false)) {
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
            'activeRoutePartialWhat',
            function ($context): string {
                $args       = func_get_args();
                $route      = $args[1]; // name of the route.
                $what       = $args[2]; // name of the route.
                $activeWhat = $context['what'] ?? false;

                if ($what === $activeWhat && !(strpos(Route::getCurrentRoute()->getName(), $route) === false)) {
                    return 'active';
                }

                return '';
            },
            ['needs_context' => true]
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
            'activeRouteStrict',
            function (): string {
                $args  = func_get_args();
                $route = $args[0]; // name of the route.

                if (Route::getCurrentRoute()->getName() === $route) {
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
            'balance',
            function (?Account $account): string {
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
            'env',
            function (string $name, string $default): string {
                return env($name, $default);
            }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function formatFilesize(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'filesize',
            function (int $size): string {

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
     * @return Twig_SimpleFunction
     */
    protected function getCurrencyCode(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'getCurrencyCode',
            function (): string {
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
            'getCurrencySymbol',
            function (): string {
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
            'mimeIcon',
            function (string $string): string {
                switch ($string) {
                    default:
                        return 'fa-file-o';
                    case 'application/pdf':
                        return 'fa-file-pdf-o';
                    case 'image/png':
                    case 'image/jpeg':
                        return 'fa-file-image-o';
                }
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function phpdate()
    {
        return new Twig_SimpleFunction(
            'phpdate',
            function (string $str): string {
                return date($str);
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function steamPositive()
    {
        return new Twig_SimpleFunction(
            'steam_positive',
            function (string $str): string {
                return Steam::positive($str);
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    private function getAmountFromJournal()
    {
        return new Twig_SimpleFunction(
            'getAmount',
            function (TransactionJournal $journal): string {
                return $journal->amount();
            }
        );
    }
}
