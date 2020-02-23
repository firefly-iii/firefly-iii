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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use League\CommonMark\CommonMarkConverter;
use Route;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TwigSupport.
 */
class General extends AbstractExtension
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
            $this->phpdate(),
            $this->activeRouteStrict(),
            $this->activeRoutePartial(),
            $this->activeRoutePartialObjectType(),
            $this->formatDate(),
            $this->getMetaField(),
            $this->hasRole(),
        ];
    }

    /**
     * Will return "active" when a part of the route matches the argument.
     * ie. "accounts" will match "accounts.index".
     *
     * @return TwigFunction
     */
    protected function activeRoutePartial(): TwigFunction
    {
        return new TwigFunction(
            'activeRoutePartial',
            static function (): string {
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
     * @return TwigFunction
     */
    protected function activeRoutePartialObjectType(): TwigFunction
    {
        return new TwigFunction(
            'activeRoutePartialObjectType',
            static function ($context): string {
                [, $route, $objectType] = func_get_args();
                $activeObjectType = $context['objectType'] ?? false;

                if ($objectType === $activeObjectType && !(false === stripos(Route::getCurrentRoute()->getName(), $route))) {
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
     * @return TwigFunction
     */
    protected function activeRouteStrict(): TwigFunction
    {
        return new TwigFunction(
            'activeRouteStrict',
            static function (): string {
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
     * Show account balance. Only used on the front page of Firefly III.
     *
     * @return TwigFilter
     */
    protected function balance(): TwigFilter
    {
        return new TwigFilter(
            'balance',
            static function (?Account $account): string {
                if (null === $account) {
                    return 'NULL';
                }
                /** @var Carbon $date */
                $date = session('end', Carbon::now()->endOfMonth());

                return app('steam')->balance($account, $date);
            }
        );
    }

    /**
     * Formats a string as a thing by converting it to a Carbon first.
     *
     * @return TwigFunction
     */
    protected function formatDate(): TwigFunction
    {
        return new TwigFunction(
            'formatDate',
            function (string $date, string $format): string {
                $carbon = new Carbon($date);

                return $carbon->formatLocalized($format);
            }
        );
    }

    /**
     * Used to convert 1024 to 1kb etc.
     *
     * @return TwigFilter
     */
    protected function formatFilesize(): TwigFilter
    {
        return new TwigFilter(
            'filesize',
            static function (int $size): string {
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
     * @return TwigFunction
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
     *
     * @return TwigFunction
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

    /**
     * @return TwigFilter
     */
    protected function markdown(): TwigFilter
    {
        return new TwigFilter(
            'markdown',
            static function (string $text): string {
                $converter = new CommonMarkConverter;

                return $converter->convertToHtml($text);
            }, ['is_safe' => ['html']]
        );
    }

    /**
     * Show icon with attachment.
     *
     * @return TwigFilter
     */
    protected function mimeIcon(): TwigFilter
    {
        return new TwigFilter(
            'mimeIcon',
            static function (string $string): string {
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
     * Basic example thing for some views.
     *
     * @return TwigFunction
     */
    protected function phpdate(): TwigFunction
    {
        return new TwigFunction(
            'phpdate',
            static function (string $str): string {
                return date($str);
            }
        );
    }
}
