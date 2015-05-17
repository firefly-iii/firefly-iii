<?php

namespace FireflyIII\Support\Twig;


use App;
use FireflyIII\Models\TransactionJournal;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Journal
 *
 * @package FireflyIII\Support\Twig
 */
class Journal extends Twig_Extension
{

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return array
     */
    public function getFilters()
    {
        $filters = [];

        $filters[] = new Twig_SimpleFilter(
            'typeIcon', function (TransactionJournal $journal) {
            $type = $journal->transactionType->type;

            switch ($type) {
                case 'Withdrawal':
                    return '<span class="glyphicon glyphicon-arrow-left" title="' . trans('firefly.withdrawal') . '"></span>';
                    break;
                case 'Deposit':
                    return '<span class="glyphicon glyphicon-arrow-right" title="' . trans('firefly.deposit') . '"></span>';
                    break;
                case 'Transfer':
                    return '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case 'Opening balance':
                    return '<span class="glyphicon glyphicon-ban-circle" title="' . trans('firefly.openingBalance') . '"></span>';
                    break;
                default:
                    return '';
                    break;
            }


        }, ['is_safe' => ['html']]
        );

        return $filters;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
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
            if ($journal->tags->count() == 0) {
                return App::make('amount')->formatJournal($journal);
            }
            foreach ($journal->tags as $tag) {
                if ($tag->tagMode == 'balancingAct') {
                    // return tag formatted for a "balancing act".
                    $amount = App::make('amount')->formatJournal($journal, false);

                    return '<a href="' . route('tags.show', $tag->id) . '" class="label label-success" title="' . $amount
                           . '"><i class="fa fa-fw fa-refresh"></i> ' . $tag->tag . '</span>';
                }
            }


            return 'TODO: ' . $journal->amount;
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
