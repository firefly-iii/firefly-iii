<?php
/**
 * Translation.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
            'journalLinkTranslation', function (int $linkTypeId, string $direction, string $original) {
            $key         = sprintf('firefly.%d_%s', $linkTypeId, $direction);
            $translation = trans($key);
            if ($key === $translation) {
                return $original;
            }

            return $translation;


        }, ['is_safe' => ['html']]
        );
    }
}
