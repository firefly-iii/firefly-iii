<?php
/**
 * PiggyBank.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
