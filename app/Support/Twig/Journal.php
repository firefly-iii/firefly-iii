<?php

namespace FireflyIII\Support\Twig;


use App;
use FireflyIII\Models\Tag;
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
                    $txt = '<i class="fa fa-long-arrow-left fa-fw" title="' . trans('firefly.withdrawal') . '"></i>';
                    break;
                case 'Deposit':
                    $txt = '<i class="fa fa-long-arrow-right fa-fw" title="' . trans('firefly.deposit') . '"></i>';
                    break;
                case 'Transfer':
                    $txt = '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case 'Opening balance':
                    $txt = '<i class="fa-fw fa fa-ban" title="' . trans('firefly.openingBalance') . '"></i>';
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
            $count  = $journal->tags->count();
            $string = '';

            if ($count === 0) {
                $string = $this->relevantTagsNoTags($journal);
            }

            if ($count === 1) {
                $string = $this->relevantTagsSingle($journal);
            }

            if ($count > 1) {
                $string = $this->relevantTagsMulti($journal);
            }

            $cache->store($string);

            return $string;
        }
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    protected function relevantTagsNoTags(TransactionJournal $journal)
    {
        return App::make('amount')->formatJournal($journal);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    protected function relevantTagsSingle(TransactionJournal $journal)
    {
        $tag = $journal->tags()->first();

        return $this->formatJournalByTag($journal, $tag);
    }

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return string
     */
    protected function formatJournalByTag(TransactionJournal $journal, Tag $tag)
    {
        if ($tag->tagMode == 'balancingAct') {
            // return tag formatted for a "balancing act", even if other
            // tags are present.
            $amount = App::make('amount')->format($journal->actual_amount, false);
            $string = '<a href="' . route('tags.show', [$tag->id]) . '" class="label label-success" title="' . $amount
                      . '"><i class="fa fa-fw fa-refresh"></i> ' . $tag->tag . '</a>';

            return $string;
        }

        if ($tag->tagMode == 'advancePayment') {
            if ($journal->transactionType->type == 'Deposit') {
                $amount = App::make('amount')->formatJournal($journal, false);
                $string = '<a href="' . route('tags.show', [$tag->id]) . '" class="label label-success" title="' . $amount
                          . '"><i class="fa fa-fw fa-sort-numeric-desc"></i> ' . $tag->tag . '</a>';

                return $string;
            }

            /*
            * AdvancePayment with a withdrawal will show the amount with a link to
            * the tag. The TransactionJournal should properly calculate the amount.
           */
            if ($journal->transactionType->type == 'Withdrawal') {
                $amount = App::make('amount')->formatJournal($journal);

                $string = '<a href="' . route('tags.show', [$tag->id]) . '">' . $amount . '</a>';

                return $string;
            }
        }


        return $this->relevantTagsNoTags($journal);
    }

    /**
     * If a transaction journal has multiple tags, we'll have to gamble. FF3
     * does not yet block adding multiple 'special' tags so we must wing it.
     *
     * We grab the first special tag (for advancePayment and for balancingAct
     * and try to format those. If they're not present (it's all normal tags),
     * we can format like any other journal.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    protected function relevantTagsMulti(TransactionJournal $journal)
    {
        $firstBalancingAct = $journal->tags()->where('tagMode', 'balancingAct')->first();
        if ($firstBalancingAct) {
            return $this->formatJournalByTag($journal, $firstBalancingAct);
        }

        $firstAdvancePayment = $journal->tags()->where('tagMode', 'advancePayment')->first();
        if ($firstAdvancePayment) {
            return $this->formatJournalByTag($journal, $firstAdvancePayment);
        }

        return $this->relevantTagsNoTags($journal);
    }
}
