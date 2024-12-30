<?php

/**
 * Translation.php
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

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class Budget.
 */
class Translation extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                '_',
                static function ($name) {
                    return (string) trans(sprintf('firefly.%s', $name));
                },
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            $this->journalLinkTranslation(),
            $this->laravelTranslation(),
        ];
    }

    public function journalLinkTranslation(): TwigFunction
    {
        return new TwigFunction(
            'journalLinkTranslation',
            static function (string $direction, string $original) {
                $key         = sprintf('firefly.%s_%s', $original, $direction);
                $translation = trans($key);
                if ($key === $translation) {
                    return $original;
                }

                return $translation;
            },
            ['is_safe' => ['html']]
        );
    }

    public function laravelTranslation(): TwigFunction
    {
        return new TwigFunction(
            '__',
            static function (string $key) {
                $translation = trans($key);
                if ($key === $translation) {
                    return $key;
                }

                return $translation;
            }
        );
    }
}
