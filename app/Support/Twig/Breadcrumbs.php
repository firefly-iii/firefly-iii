<?php
declare(strict_types=1);
/*
 * Breadcrumbs.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Twig;

use FireflyIII\Exceptions\FireflyException;
use Route;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Breadcrumbs
 */
class Breadcrumbs extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            $this->renderBreadcrumb(),
        ];
    }

    /**
     * @return TwigFunction
     */
    private function renderBreadcrumb(): TwigFunction
    {
        return new TwigFunction(
            'ff3bc',
            static function (?array $args): string {
                $name = Route::getCurrentRoute()->getName() ?? '';

                // loop for actual breadcrumb:
                $arr = config(sprintf('bc.%s', $name));

                if (null === $arr) {
                    throw new FireflyException(sprintf('No breadcrumbs for route "%s".', $name));
                }
                $breadcrumbs = $this->getBreadcrumbs($arr);

                return $this->getHtml($breadcrumbs, $args);

            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * @param array $arr
     *
     * @return array
     * @throws FireflyException
     */
    private function getBreadcrumbs(array $arr)
    {
        $breadcrumbs = [];
        $hasParent   = true;
        $loop        = 0;
        while (true === $hasParent && $loop < 30) {
            $breadcrumbs[] = $arr;
            if (null === $arr['parent']) {
                $hasParent = false;

            }
            if (null !== $arr['parent']) {
                $arr = config(sprintf('bc.%s', $arr['parent']));
                if (null === $arr) {
                    throw new FireflyException(sprintf('No (2) breadcrumbs for route "%s".', $arr['parent']));
                }
            }
            $loop++; // safety catch
        }

        // reverse order
        return array_reverse($breadcrumbs);
    }

    /**
     * @param array      $breadcrumbs
     * @param array|null $args
     *
     * @return string
     */
    private function getHtml(array $breadcrumbs, ?array $args): string
    {
        // get HTML
        $html = '<ol class="breadcrumb float-sm-right">';
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $class = 'breadcrumb-item';
            if ($index === count($breadcrumbs) - 1) {
                // active!
                $class = 'breadcrumb-item active';
            }
            $route = '#';
            if (null !== $breadcrumb['static_route']) {
                $route = route($breadcrumb['static_route']);
            }
            if (null !== $breadcrumb['dynamic_route']) {
                $route = route($breadcrumb['dynamic_route'], $args[$index - 1] ?? []);
            }
            $html .= sprintf('<li class="%1$s"><a href="%2$s" title="%3$s">%3$s</a></li>', $class, $route, trans($breadcrumb['title']));
        }

        return $html . '</ol>';
    }
}