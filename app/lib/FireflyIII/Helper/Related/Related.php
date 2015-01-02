<?php
namespace FireflyIII\Helper\Related;

use FireflyIII\Database\SwitchUser;
use Illuminate\Support\Collection;

/**
 * Class Related
 *
 * @package FireflyIII\Helper\Related
 */
class Related implements RelatedInterface
{
    use SwitchUser;

    /**
     *
     */
    public function __construct()
    {
        $this->setUser(\Auth::user());
    }

    /**
     * @param array $objectIds
     *
     * @return Collection
     */
    public function getJournalsByIds(array $objectIds)
    {
        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $repository */
        $repository = \App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        return $repository->getByIds($objectIds);
    }

    /**
     * @param string              $query
     * @param \TransactionJournal $journal
     *
     * @return Collection
     */
    public function search($query, \TransactionJournal $journal)
    {
        $start = clone $journal->date;
        $end   = clone $journal->date;
        $start->startOfMonth();
        $end->endOfMonth();

        // get already related transactions:
        $exclude = [$journal->id];
        foreach ($journal->transactiongroups()->get() as $group) {
            foreach ($group->transactionjournals() as $current) {
                $exclude[] = $current->id;
            }
        }
        $exclude = array_unique($exclude);

        $query = $this->getUser()->transactionjournals()
                      ->withRelevantData()
                      ->before($end)
                      ->after($start)
                      ->whereNotIn('id', $exclude)
                      ->where('description', 'LIKE', '%' . $query . '%')
                      ->get();

        return $query;
    }
}
