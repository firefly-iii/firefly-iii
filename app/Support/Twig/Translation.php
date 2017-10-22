<?php
/**
 * Translation.php
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

use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 *
 * Class Budget
 *
 * @package FireflyIII\Support\Twig
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
            '_', function ($name) {

            return strval(trans(sprintf('firefly.%s', $name)));

        }, ['is_safe' => ['html']]
        );

        return $filters;
    }


    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->journalLinkTranslation(),

        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'FireflyIII\Support\Twig\Translation';
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalLinkTranslation(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalLinkTranslation', function (string $direction, string $original) {
            $key         = sprintf('firefly.%s_%s', $original, $direction);
            $translation = trans($key);
            if ($key === $translation) {
                return $original;
            }

            return $translation;


        }, ['is_safe' => ['html']]
        );
    }
}
