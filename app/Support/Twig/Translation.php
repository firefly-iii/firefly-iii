<?php
/**
 * Translation.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Budget.
 */
class Translation extends Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            '_',
            function ($name) {
                return (string)trans(sprintf('firefly.%s', $name));
            },
            ['is_safe' => ['html']]
        );

        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->journalLinkTranslation(),
        ];
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalLinkTranslation(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalLinkTranslation',
            function (string $direction, string $original) {
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
}
