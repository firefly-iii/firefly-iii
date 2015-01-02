<?php
use FireflyIII\Helper\Related\RelatedInterface;
use Illuminate\Support\Collection;

/**
 * Class RelatedController
 */
class RelatedController extends BaseController
{

    protected $_repository;

    public function __construct(RelatedInterface $repository)
    {
        $this->_repository = $repository;

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function alreadyRelated(TransactionJournal $journal)
    {
        $ids = [];
        /** @var TransactionGroup $group */
        foreach ($journal->transactiongroups()->get() as $group) {
            /** @var TransactionJournal $loopJournal */
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id != $journal->id) {
                    $ids[] = $loopJournal->id;
                }
            }
        }
        $unique = array_unique($ids);
        if (count($unique) > 0) {

            $set = $this->_repository->getJournalsByIds($unique);
            $set->each(
                function (TransactionJournal $journal) {
                    $journal->amount = Amount::format($journal->getAmount());
                }
            );

            return Response::json($set->toArray());
        } else {
            return Response::json((new Collection)->toArray());
        }
    }

    /**
     * @param TransactionJournal $parentJournal
     * @param TransactionJournal $childJournal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function relate(TransactionJournal $parentJournal, TransactionJournal $childJournal)
    {
        $group           = new TransactionGroup;
        $group->relation = 'balance';
        $group->user_id  = $this->_repository->getUser()->id;
        $group->save();
        $group->transactionjournals()->save($parentJournal);
        $group->transactionjournals()->save($childJournal);

        return Response::json(true);

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\View\View
     */
    public function related(TransactionJournal $journal)
    {
        $groups  = $journal->transactiongroups()->get();
        $members = new Collection;
        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            /** @var TransactionJournal $loopJournal */
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id != $journal->id) {
                    $members->push($loopJournal);
                }
            }
        }

        return View::make('related.relate', compact('journal', 'members'));
    }

    /**
     * @param TransactionJournal $parentJournal
     * @param TransactionJournal $childJournal
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function removeRelation(TransactionJournal $parentJournal, TransactionJournal $childJournal)
    {
        $groups = $parentJournal->transactiongroups()->get();
        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id == $childJournal->id) {
                    // remove from group:
                    $group->transactionjournals()->detach($childJournal);
                }
            }
            if ($group->transactionjournals()->count() == 1) {
                $group->delete();
            }
        }

        return Response::json(true);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(TransactionJournal $journal)
    {

        $search = e(trim(Input::get('searchValue')));

        $result = $this->_repository->search($search, $journal);
        $result->each(
            function (TransactionJournal $j) {
                $j->amount = Amount::format($j->getAmount());
            }
        );

        return Response::json($result->toArray());
    }

}
