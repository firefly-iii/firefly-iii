<?php
/**
 * PiggyBank.php
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

use FireflyIII\Models\PiggyBank as PB;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 *
 * Class PiggyBank
 *
 * @package FireflyIII\Support\Twig
 */
class PiggyBank extends Twig_Extension
{

    /**
     *
     */
    public function getFunctions(): array
    {
        $functions = [];

        $functions[] = new Twig_SimpleFunction(
            'currentRelevantRepAmount', function (PB $piggyBank) {
            return $piggyBank->currentRelevantRep()->currentamount;
        }
        );

        $functions[] = new Twig_SimpleFunction(
            'suggestedMonthlyAmount', function (PB $piggyBank) {
            return $piggyBank->getSuggestedMonthlyAmount();
        }
        );

        return $functions;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'FireflyIII\Support\Twig\PiggyBank';
    }
}
