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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use League\CommonMark\CommonMarkConverter;
use Route;
use Steam;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class TwigSupport.
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
            $this->markdown(),
        ];
    }

    /**
     * {@inheritdoc}
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
            $this->formatDate(),
        ];
    }

    /**
     * {@inheritdoc}
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
                if (!(false === strpos($name, $route))) {
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

                if ($what === $activeWhat && !(false === strpos(Route::getCurrentRoute()->getName(), $route))) {
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
                if (null === $account) {
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
     * @return Twig_SimpleFunction
     */
    protected function formatDate()
    {
        return new Twig_SimpleFunction(
            'formatDate',
            function (string $date, string $format): string {
                $carbon = new Carbon($date);

                return $carbon->formatLocalized($format);
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
    protected function markdown(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'markdown',
            function (string $text): string {
                $converter = new CommonMarkConverter;

                return $converter->convertToHtml($text);
            }, ['is_safe' => ['html']]
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
                    /* image */
                    case 'image/png':
                    case 'image/jpeg':
                    case 'image/svg+xml':
                    case 'image/heic':
                    case 'image/heic-sequence':
                    case 'application/vnd.oasis.opendocument.image':
                        return 'fa-file-image-o';
                    /* MS word */
                    case 'application/msword':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.template':
                    case 'application/x-iwork-pages-sffpages':
                    case 'application/vnd.sun.xml.writer':
                    case 'application/vnd.sun.xml.writer.template':
                    case 'application/vnd.sun.xml.writer.global':
                    case 'application/vnd.stardivision.writer':
                    case 'application/vnd.stardivision.writer-global':
                    case 'application/vnd.oasis.opendocument.text':
                    case 'application/vnd.oasis.opendocument.text-template':
                    case 'application/vnd.oasis.opendocument.text-web':
                    case 'application/vnd.oasis.opendocument.text-master':
                        return 'fa-file-word-o';
                    /* MS excel */
                    case 'application/vnd.ms-excel':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.template':
                    case 'application/vnd.sun.xml.calc':
                    case 'application/vnd.sun.xml.calc.template':
                    case 'application/vnd.stardivision.calc':
                    case 'application/vnd.oasis.opendocument.spreadsheet':
                    case 'application/vnd.oasis.opendocument.spreadsheet-template':
                        return 'fa-file-excel-o';
                    /* MS powerpoint */
                    case 'application/vnd.ms-powerpoint':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.template':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
                    case 'application/vnd.sun.xml.impress':
                    case 'application/vnd.sun.xml.impress.template':
                    case 'application/vnd.stardivision.impress':
                    case 'application/vnd.oasis.opendocument.presentation':
                    case 'application/vnd.oasis.opendocument.presentation-template':
                        return 'fa-file-powerpoint-o';
                    /* calc */
                    case 'application/vnd.sun.xml.draw':
                    case 'application/vnd.sun.xml.draw.template':
                    case 'application/vnd.stardivision.draw':
                    case 'application/vnd.oasis.opendocument.chart':
                        return 'fa-paint-brush';
                    case 'application/vnd.oasis.opendocument.graphics':
                    case 'application/vnd.oasis.opendocument.graphics-template':
                    case 'application/vnd.sun.xml.math':
                    case 'application/vnd.stardivision.math':
                    case 'application/vnd.oasis.opendocument.formula':
                    case 'application/vnd.oasis.opendocument.database':
                        return 'fa-calculator';

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
