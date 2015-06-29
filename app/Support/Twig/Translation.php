<?php

namespace FireflyIII\Support\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

/**
 * @codeCoverageIgnore
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
    public function getFilters()
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            '_', function ($name) {

            return trans('firefly.' . $name);

        }, ['is_safe' => ['html']]
        );

        return $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\Translation';
    }
}
