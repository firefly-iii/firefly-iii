<?php

/**
 * General.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Search\OperatorQuerySearch;
use Illuminate\Support\Facades\Log;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Illuminate\Support\Facades\Route;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TwigSupport.
 */
class General extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            $this->balance(),
            $this->formatFilesize(),
            $this->mimeIcon(),
            $this->markdown(),
            $this->phpHostName(),
        ];
    }

    /**
     * Show account balance. Only used on the front page of Firefly III.
     */
    protected function balance(): TwigFilter
    {
        return new TwigFilter(
            'balance',
            static function (?Account $account): string {
                if (null === $account) {
                    return '0';
                }

                /** @var Carbon $date */
                $date            = session('end', today(config('app.timezone'))->endOfMonth());
                Log::debug(sprintf('twig balance: Call finalAccountBalance with date/time "%s"', $date->toIso8601String()));
                $info            = Steam::finalAccountBalance($account, $date);
                $currency        = Steam::getAccountCurrency($account);
                $default         = Amount::getNativeCurrency();
                $convertToNative = Amount::convertToNative();
                $useNative       = $convertToNative && $default->id !== $currency->id;
                $currency ??= $default;
                $strings         = [];
                foreach ($info as $key => $balance) {
                    if ('balance' === $key) {
                        // balance in account currency.
                        if (!$useNative) {
                            $strings[] = app('amount')->formatAnything($currency, $balance, false);
                        }

                        continue;
                    }
                    if ('native_balance' === $key) {
                        // balance in native currency.
                        if ($useNative) {
                            $strings[] = app('amount')->formatAnything($default, $balance, false);
                        }

                        continue;
                    }
                    // for multi currency accounts.
                    if ($useNative && $key !== $default->code) {
                        $strings[] = app('amount')->formatAnything(TransactionCurrency::where('code', $key)->first(), $balance, false);
                    }
                }

                return implode(', ', $strings);
                // return app('steam')->balance($account, $date);
            }
        );
    }

    /**
     * Used to convert 1024 to 1kb etc.
     */
    protected function formatFilesize(): TwigFilter
    {
        return new TwigFilter(
            'filesize',
            static function (int $size): string {
                // less than one GB, more than one MB
                if ($size < (1024 * 1024 * 2014) && $size >= (1024 * 1024)) {
                    return round($size / (1024 * 1024), 2).' MB';
                }

                // less than one MB
                if ($size < (1024 * 1024)) {
                    return round($size / 1024, 2).' KB';
                }

                return $size.' bytes';
            }
        );
    }

    /**
     * Show icon with attachment.
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    protected function mimeIcon(): TwigFilter
    {
        return new TwigFilter(
            'mimeIcon',
            static fn (string $string): string => match ($string) {
                'application/pdf'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           => 'fa-file-pdf-o',
                'image/png', 'image/jpeg', 'image/svg+xml', 'image/heic', 'image/heic-sequence', 'application/vnd.oasis.opendocument.image'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 => 'fa-file-image-o',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'application/x-iwork-pages-sffpages', 'application/vnd.sun.xml.writer', 'application/vnd.sun.xml.writer.template', 'application/vnd.sun.xml.writer.global', 'application/vnd.stardivision.writer', 'application/vnd.stardivision.writer-global', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.text-template', 'application/vnd.oasis.opendocument.text-web', 'application/vnd.oasis.opendocument.text-master' => 'fa-file-word-o',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'application/vnd.sun.xml.calc', 'application/vnd.sun.xml.calc.template', 'application/vnd.stardivision.calc', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.spreadsheet-template'                                                                                                                                                                                                                          => 'fa-file-excel-o',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats-officedocument.presentationml.template', 'application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'application/vnd.sun.xml.impress', 'application/vnd.sun.xml.impress.template', 'application/vnd.stardivision.impress', 'application/vnd.oasis.opendocument.presentation', 'application/vnd.oasis.opendocument.presentation-template'                                                                                                                       => 'fa-file-powerpoint-o',
                'application/vnd.sun.xml.draw', 'application/vnd.sun.xml.draw.template', 'application/vnd.stardivision.draw', 'application/vnd.oasis.opendocument.chart'                                                                                                                                                                                                                                                                                                                                                                                                                                                                    => 'fa-paint-brush',
                'application/vnd.oasis.opendocument.graphics', 'application/vnd.oasis.opendocument.graphics-template', 'application/vnd.sun.xml.math', 'application/vnd.stardivision.math', 'application/vnd.oasis.opendocument.formula', 'application/vnd.oasis.opendocument.database'                                                                                                                                                                                                                                                                                                                                                     => 'fa-calculator',
                default                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     => 'fa-file-o',
            },
            ['is_safe' => ['html']]
        );
    }

    protected function markdown(): TwigFilter
    {
        return new TwigFilter(
            'markdown',
            static function (string $text): string {
                $converter = new GithubFlavoredMarkdownConverter(
                    [
                        'allow_unsafe_links' => false,
                        'max_nesting_level'  => 5,
                        'html_input'         => 'escape',
                    ]
                );

                return (string) $converter->convert($text);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Show URL host name
     */
    protected function phpHostName(): TwigFilter
    {
        return new TwigFilter(
            'phphost',
            static function (string $string): string {
                $proto = (string) \Safe\parse_url($string, PHP_URL_SCHEME);
                $host  = (string) \Safe\parse_url($string, PHP_URL_HOST);

                return e(sprintf('%s://%s', $proto, $host));
            }
        );
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            $this->phpdate(),
            $this->activeRouteStrict(),
            $this->activeRoutePartial(),
            $this->activeRoutePartialObjectType(),
            $this->menuOpenRoutePartial(),
            $this->formatDate(),
            $this->getMetaField(),
            $this->hasRole(),
            $this->getRootSearchOperator(),
            $this->carbonize(),
        ];
    }

    /**
     * Basic example thing for some views.
     */
    protected function phpdate(): TwigFunction
    {
        return new TwigFunction(
            'phpdate',
            static fn (string $str): string => date($str)
        );
    }

    /**
     * Will return "active" when the current route matches the given argument
     * exactly.
     */
    protected function activeRouteStrict(): TwigFunction
    {
        return new TwigFunction(
            'activeRouteStrict',
            static function (): string {
                $args  = func_get_args();
                $route = $args[0]; // name of the route.

                if (\Route::getCurrentRoute()->getName() === $route) {
                    return 'active';
                }

                return '';
            }
        );
    }

    /**
     * Will return "active" when a part of the route matches the argument.
     * ie. "accounts" will match "accounts.index".
     */
    protected function activeRoutePartial(): TwigFunction
    {
        return new TwigFunction(
            'activeRoutePartial',
            static function (): string {
                $args  = func_get_args();
                $route = $args[0]; // name of the route.
                $name  = \Route::getCurrentRoute()->getName() ?? '';
                if (str_contains($name, $route)) {
                    return 'active';
                }

                return '';
            }
        );
    }

    /**
     * This function will return "active" when the current route matches the first argument (even partly)
     * but, the variable $objectType has been set and matches the second argument.
     */
    protected function activeRoutePartialObjectType(): TwigFunction
    {
        return new TwigFunction(
            'activeRoutePartialObjectType',
            static function ($context): string {
                [, $route, $objectType] = func_get_args();
                $activeObjectType       = $context['objectType'] ?? false;

                if ($objectType === $activeObjectType
                    && false !== stripos(
                        (string) \Route::getCurrentRoute()->getName(),
                        (string) $route
                    )) {
                    return 'active';
                }

                return '';
            },
            ['needs_context' => true]
        );
    }

    /**
     * Will return "menu-open" when a part of the route matches the argument.
     * ie. "accounts" will match "accounts.index".
     */
    protected function menuOpenRoutePartial(): TwigFunction
    {
        return new TwigFunction(
            'menuOpenRoutePartial',
            static function (): string {
                $args  = func_get_args();
                $route = $args[0]; // name of the route.
                $name  = \Route::getCurrentRoute()->getName() ?? '';
                if (str_contains($name, $route)) {
                    return 'menu-open';
                }

                return '';
            }
        );
    }

    /**
     * Formats a string as a thing by converting it to a Carbon first.
     */
    protected function formatDate(): TwigFunction
    {
        return new TwigFunction(
            'formatDate',
            static function (string $date, string $format): string {
                $carbon = new Carbon($date);

                return $carbon->isoFormat($format);
            }
        );
    }

    /**
     * TODO Remove me when v2 hits.
     */
    protected function getMetaField(): TwigFunction
    {
        return new TwigFunction(
            'accountGetMetaField',
            static function (Account $account, string $field): string {
                /** @var AccountRepositoryInterface $repository */
                $repository = app(AccountRepositoryInterface::class);
                $result     = $repository->getMetaValue($account, $field);
                if (null === $result) {
                    return '';
                }

                return $result;
            }
        );
    }

    /**
     * Will return true if the user is of role X.
     */
    protected function hasRole(): TwigFunction
    {
        return new TwigFunction(
            'hasRole',
            static function (string $role): bool {
                $repository = app(UserRepositoryInterface::class);
                if ($repository->hasRole(auth()->user(), $role)) {
                    return true;
                }

                return false;
            }
        );
    }

    protected function getRootSearchOperator(): TwigFunction
    {
        return new TwigFunction(
            'getRootSearchOperator',
            static function (string $operator): string {
                $result = OperatorQuerySearch::getRootOperator($operator);

                return str_replace('-', 'not_', $result);
            }
        );
    }

    protected function carbonize(): TwigFunction
    {
        return new TwigFunction(
            'carbonize',
            static fn (string $date): Carbon => new Carbon($date, config('app.timezone'))
        );
    }
}
