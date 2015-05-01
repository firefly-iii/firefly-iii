<?php

namespace FireflyIII\Support\Twig;


use FireflyIII\Models\TransactionJournal;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Journals
 *
 * @package FireflyIII\Support\Twig
 */
class Journals extends Twig_Extension
{

    public function getFilters()
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            'typeIcon', function (TransactionJournal $journal) {
            $type = $journal->transactionType->type;
            if ($type == 'Withdrawal') {
                return '<span class="glyphicon glyphicon-arrow-left" title="Withdrawal"></span>';
            }
            if ($type == 'Deposit') {
                return '<span class="glyphicon glyphicon-arrow-right" title="Deposit"></span>';
            }
            if ($type == 'Transfer') {
                return '<i class="fa fa-fw fa-exchange" title="Transfer"></i>';
            }
            if ($type == 'Opening balance') {
                return '<span class="glyphicon glyphicon-ban-circle" title="Opening balance"></span>';
            }


        }, ['is_safe' => ['html']]
        );

        return $filters;
    }

    public function getFunctions()
    {
        $functions = [];

        $functions[] = new Twig_SimpleFunction(
            'invalidJournal', function (TransactionJournal $journal) {
            if (!isset($journal->transactions[1]) || !isset($journal->transactions[0])) {
                return true;
            }

            return false;
        }
        );

        $functions[] = new Twig_SimpleFunction(
            'relevantTags', function (TransactionJournal $journal) {
            return 'TODO'.$journal->amount;
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
        return 'FireflyIII\Support\Twig\Journals';
    }
}