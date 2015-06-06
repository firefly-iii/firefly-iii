<?php

namespace FireflyIII\Support\Twig;


use App;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
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
     * @return array
     */
    public function getFilters()
    {
        $filters = [$this->typeIcon()];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $functions = [
            $this->invalidJournal(),
            $this->relevantTags()
        ];

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

    /**
     * @return Twig_SimpleFilter
     */
    protected function typeIcon()
    {
        return new Twig_SimpleFilter(
            'typeIcon', function (TransactionJournal $journal) {

            $cache = new CacheProperties();
            $cache->addProperty($journal->id);
            $cache->addProperty('typeIcon');
            if ($cache->has()) {
                return $cache->get(); // @codeCoverageIgnore
            }

            $type = $journal->transactionType->type;

            switch ($type) {
                case 'Withdrawal':
                    $txt = '<span class="glyphicon glyphicon-arrow-left" title="' . trans('firefly.withdrawal') . '"></span>';
                    break;
                case 'Deposit':
                    $txt = '<span class="glyphicon glyphicon-arrow-right" title="' . trans('firefly.deposit') . '"></span>';
                    break;
                case 'Transfer':
                    $txt = '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case 'Opening balance':
                    $txt = '<span class="glyphicon glyphicon-ban-circle" title="' . trans('firefly.openingBalance') . '"></span>';
                    break;
                default:
                    $txt = '';
                    break;
            }
            $cache->store($txt);

            return $txt;


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function invalidJournal()
    {
        return new Twig_SimpleFunction(
            'invalidJournal', function (TransactionJournal $journal) {
            if (!isset($journal->transactions[1]) || !isset($journal->transactions[0])) {
                return true;
            }

            return false;
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    protected function relevantTags()
    {
        return new Twig_SimpleFunction(
            'relevantTags', function (TransactionJournal $journal) {
            $cache = new CacheProperties;
            $cache->addProperty('relevantTags');
            $cache->addProperty($journal->id);

            if ($cache->has()) {
                return $cache->get(); // @codeCoverageIgnore
            }

            if ($journal->tags->count() == 0) {
                $string = App::make('amount')->formatJournal($journal);
                $cache->store($string);

                return $string;
            }


            foreach ($journal->tags as $tag) {
                if ($tag->tagMode == 'balancingAct') {
                    // return tag formatted for a "balancing act", even if other
                    // tags are present.
                    $amount = App::make('amount')->format($journal->actual_amount, false);
                    $string = '<a href="' . route('tags.show', [$tag->id]) . '" class="label label-success" title="' . $amount
                              . '"><i class="fa fa-fw fa-refresh"></i> ' . $tag->tag . '</a>';
                    $cache->store($string);

                    return $string;
                }

                /*
                 * AdvancePayment with a deposit will show the tag instead of the amount:
                 */
                if ($tag->tagMode == 'advancePayment' && $journal->transactionType->type == 'Deposit') {
                    $amount = App::make('amount')->formatJournal($journal, false);
                    $string = '<a href="' . route('tags.show', [$tag->id]) . '" class="label label-success" title="' . $amount
                              . '"><i class="fa fa-fw fa-sort-numeric-desc"></i> ' . $tag->tag . '</a>';
                    $cache->store($string);

                    return $string;
                }
                /*
                 * AdvancePayment with a withdrawal will show the amount with a link to
                 * the tag. The TransactionJournal should properly calculate the amount.
                 */
                if ($tag->tagMode == 'advancePayment' && $journal->transactionType->type == 'Withdrawal') {
                    $amount = App::make('amount')->formatJournal($journal);

                    $string = '<a href="' . route('tags.show', [$tag->id]) . '">' . $amount . '</a>';
                    $cache->store($string);

                    return $string;
                }


                if ($tag->tagMode == 'nothing') {
                    // return the amount:
                    $string = App::make('amount')->formatJournal($journal);
                    $cache->store($string);

                    return $string;
                }
            }


            return 'TODO: ' . $journal->amount;
        }
        );
    }
}
