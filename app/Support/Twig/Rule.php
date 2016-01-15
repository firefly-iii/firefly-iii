<?php

namespace FireflyIII\Support\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class Rule
 * @package FireflyIII\Support\Twig
 */
class Rule extends Twig_Extension
{
    /**
     *
     */
    public function getFunctions()
    {
        $functions = [];

        $functions[] = new Twig_SimpleFunction(
            'allJournalTriggers', function () {
            return [
                'store-journal'  => trans('firefly.rule_trigger_store_journal'),
                'update-journal' => trans('firefly.rule_trigger_update_journal'),
            ];
        }
        );

        return $functions;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'FireflyIII\Support\Twig\Rule';
    }
}