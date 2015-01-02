<?php

namespace FireflyIII\Helper\Related;
use Illuminate\Support\Collection;

/**
 * Interface RelatedInterface
 *
 * @package FireflyIII\Helper\Related
 */
interface RelatedInterface
{

    /**
     * @param string              $query
     * @param \TransactionJournal $journal
     *
     * @return Collection
     */
    public function search($query, \TransactionJournal $journal);

    /**
     * @param array $objectIds
     *
     * @return Collection
     */
    public function getJournalsByIds(array $objectIds);

}